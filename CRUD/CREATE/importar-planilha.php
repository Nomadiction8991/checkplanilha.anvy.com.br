<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/functions/comum_functions.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Redirecionamento após sucesso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arquivo_csv = $_FILES['arquivo_csv'] ?? null;
    $posicao_comum = trim($_POST['posicao_comum'] ?? 'D16');
    $posicao_data = trim($_POST['posicao_data'] ?? 'D13');
    $posicao_cnpj = trim($_POST['posicao_cnpj'] ?? 'U5');
    $pulo_linhas = (int)($_POST['pulo_linhas'] ?? 25);
    $mapeamento_codigo = strtoupper(trim($_POST['mapeamento_codigo'] ?? 'A'));
    $mapeamento_complemento = strtoupper(trim($_POST['mapeamento_complemento'] ?? 'D'));
    $administracao = trim($_POST['administracao'] ?? '');
    $cidade = trim($_POST['cidade'] ?? '');
    $setor = isset($_POST['setor']) && $_POST['setor'] !== '' ? (int)$_POST['setor'] : null;
    $mapeamento_dependencia = strtoupper(trim($_POST['mapeamento_dependencia'] ?? 'P'));

    $mensagem = '';
    $tipo_mensagem = '';
    $sucesso = false;

    try {
        // Validações
        if (!$arquivo_csv || $arquivo_csv['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Selecione um arquivo CSV válido.');
        }
        if (empty($administracao) || empty($cidade)) {
            throw new Exception('Administração e Cidade são obrigatórias.');
        }

        $extensao = strtolower(pathinfo($arquivo_csv['name'], PATHINFO_EXTENSION));
        if ($extensao !== 'csv') {
            throw new Exception('Apenas arquivos CSV são permitidos.');
        }

        // Carregar arquivo
        $planilha = IOFactory::load($arquivo_csv['tmp_name']);
        $aba = $planilha->getActiveSheet();

        // Obter valores das células
        $valor_comum = trim($aba->getCell($posicao_comum)->getCalculatedValue());
        $valor_data = trim($aba->getCell($posicao_data)->getCalculatedValue());
        $valor_cnpj = trim($aba->getCell($posicao_cnpj)->getCalculatedValue());

        if (empty($valor_comum)) {
            throw new Exception('A célula ' . $posicao_comum . ' está vazia.');
        }

        // Converter data
        $data_mysql = null;
        if (!empty($valor_data)) {
            if (is_numeric($valor_data)) {
                $data_mysql = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor_data)->format('Y-m-d');
            } else {
                $ts = strtotime($valor_data);
                if ($ts !== false) {
                    $data_mysql = date('Y-m-d', $ts);
                }
            }
        }

    // Converter CNPJ
    $cnpj_limpo = preg_replace('/[^0-9]/', '', $valor_cnpj);

        // Procesar comum e obter ID (pode criar ou atualizar)
        $dados_comum = [
            'cnpj' => $cnpj_limpo,
            'administracao' => $administracao,
            'cidade' => $cidade,
            'setor' => $setor
        ];
        $comum_processado_id = processar_comum($conexao, $valor_comum, $dados_comum);
        if (!$comum_processado_id) {
            throw new Exception('Erro ao processar comum.');
        }

        // Carregar todas as linhas e contar candidatas (linhas de produto com código preenchido)
        $linhas = $aba->toArray();
        $linha_atual = 0;
        $registros_candidatos = 0;

        $colunaParaIndice = function($coluna) {
            $coluna = strtoupper($coluna);
            $indice = 0;
            $tamanho = strlen($coluna);
            for ($i = 0; $i < $tamanho; $i++) {
                $indice = $indice * 26 + (ord($coluna[$i]) - ord('A') + 1);
            }
            return $indice - 1;
        };
        $idx_codigo = $colunaParaIndice($mapeamento_codigo);
        $idx_complemento = $colunaParaIndice($mapeamento_complemento);
        $idx_dependencia = $colunaParaIndice($mapeamento_dependencia);

        foreach ($linhas as $linha) {
            $linha_atual++;
            if ($linha_atual <= $pulo_linhas) { continue; }
            if (empty(array_filter($linha))) { continue; }
            $codigo_tmp = isset($linha[$idx_codigo]) ? trim((string)$linha[$idx_codigo]) : '';
            if ($codigo_tmp !== '') { $registros_candidatos++; }
        }

        if ($registros_candidatos === 0) {
            throw new Exception('Nenhuma linha de produto encontrada após o cabeçalho. Verifique o mapeamento de colunas e o número de linhas a pular.');
        }

        // Iniciar transação apenas para planilha+produtos; dados do Comum já foram persistidos
        $conexao->beginTransaction();

        // Criar nova planilha vinculada ao comum
        $sql_planilha = "INSERT INTO planilhas (comum_id, posicao_cnpj, posicao_comum, posicao_data, pulo_linhas, mapeamento_colunas, data_posicao, ativo) 
                        VALUES (:comum_id, :posicao_cnpj, :posicao_comum, :posicao_data, :pulo_linhas, :mapeamento_colunas, :data_posicao, 1)";
        $stmt = $conexao->prepare($sql_planilha);
        $stmt->bindValue(':comum_id', $comum_processado_id, PDO::PARAM_INT);
        $stmt->bindValue(':posicao_cnpj', $posicao_cnpj);
        $stmt->bindValue(':posicao_comum', $posicao_comum);
        $stmt->bindValue(':posicao_data', $posicao_data);
        $stmt->bindValue(':pulo_linhas', $pulo_linhas);
        $stmt->bindValue(':mapeamento_colunas', "codigo=$mapeamento_codigo;complemento=$mapeamento_complemento;dependencia=$mapeamento_dependencia");
        $stmt->bindValue(':data_posicao', $data_mysql);
        $stmt->execute();
        $id_planilha = $conexao->lastInsertId();

        // Pré-carregar tipos de bens e dependências para matching
        $tipos_bens = [];
        $stmtTipos = $conexao->prepare("SELECT id, codigo, descricao FROM tipos_bens ORDER BY LENGTH(descricao) DESC");
        if ($stmtTipos->execute()) {
            $tipos_bens = $stmtTipos->fetchAll(PDO::FETCH_ASSOC);
        }
        $dep_map = [];
        $stmtDeps = $conexao->prepare("SELECT id, descricao FROM dependencias");
        if ($stmtDeps->execute()) {
            foreach ($stmtDeps->fetchAll(PDO::FETCH_ASSOC) as $d) {
                $dep_map[] = [
                    'id' => (int)$d['id'],
                    'k' => null, // preenchido após normalização
                    'descricao' => $d['descricao']
                ];
            }
        }

        // Funções auxiliares
        $removerAcentos = function($str) {
            $str = (string)$str;
            $s = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            if ($s === false) {
                return $str;
            }
            return $s;
        };
        $normaliza = function($str) use ($removerAcentos) {
            $str = trim((string)$str);
            $str = preg_replace('/\s+/', ' ', $str);
            $str = $removerAcentos($str);
            $str = strtoupper($str);
            return $str;
        };

        // Mapa de aliases normalizados para tipos de bens (por descricao e sinônimos separados por '/')
        $tipos_aliases = [];
        foreach ($tipos_bens as $tb) {
            $aliases = array_filter(array_map('trim', preg_split('/\s*\/\s*/', (string)$tb['descricao'])));
            $aliases[] = (string)$tb['descricao']; // inclui a descricao completa como alias
            $aliases_norm = array_unique(array_map($normaliza, $aliases));
            $tipos_aliases[] = [
                'id' => (int)$tb['id'],
                'codigo' => (int)$tb['codigo'],
                'descricao' => (string)$tb['descricao'],
                'aliases' => $aliases_norm,
            ];
        }

        // Construir chaves normalizadas para dependências
        foreach ($dep_map as &$dep) {
            $dep['k'] = $normaliza($dep['descricao']);
        }
        unset($dep);

        // Processar linhas do CSV
        $registros_importados = 0;
        $registros_erros = 0;
        $linha_atual = 0;

        foreach ($linhas as $linha) {
            $linha_atual++;

            if ($linha_atual <= $pulo_linhas) {
                continue;
            }

            if (empty(array_filter($linha))) {
                continue;
            }

            try {
                $codigo = isset($linha[$idx_codigo]) ? trim($linha[$idx_codigo]) : '';
                if (empty($codigo)) {
                    continue;
                }

                $complemento_original = isset($linha[$idx_complemento]) ? trim((string)$linha[$idx_complemento]) : '';
                $dependencia_original = isset($linha[$idx_dependencia]) ? trim((string)$linha[$idx_dependencia]) : '';

                // Parsing avançado: detectar código, tipo de bem (por código ou nome), remover prefixos e extrair BEN e COMPLEMENTO
                $texto = $complemento_original;
                $texto_norm = $normaliza($texto);

                // 1) Remover prefixo de código no início, ex: "68 -", "13.001 -", "OT-1 -"
                $codigo_detectado = null;
                if (preg_match('/^\s*(\d{1,3})(?:[\.,]\d+)?\s*\-\s*/u', $texto, $m)) {
                    $codigo_detectado = (int)$m[1];
                    $texto = preg_replace('/^\s*' . preg_quote($m[0], '/') . '/u', '', $texto);
                    $texto_norm = $normaliza($texto);
                } else {
                    // Remove padrões "OT-... -" apenas do texto (não influencia no código do tipo)
                    if (preg_match('/^\s*OT-?\d+\s*\-\s*/iu', $texto)) {
                        $texto = preg_replace('/^\s*OT-?\d+\s*\-\s*/iu', '', $texto);
                        $texto_norm = $normaliza($texto);
                    }
                }

                // 2) Descobrir tipo de bem: primeiro por código, depois por nome
                $tipo_ben_id = 0;
                $tipo_ben_codigo = null;
                $tipo_bem_desc = null;

                if ($codigo_detectado !== null) {
                    foreach ($tipos_aliases as $tb) {
                        if ((int)$tb['codigo'] === $codigo_detectado) {
                            $tipo_ben_id = (int)$tb['id'];
                            $tipo_ben_codigo = (int)$tb['codigo'];
                            $tipo_bem_desc = (string)$tb['descricao'];
                            break;
                        }
                    }
                }

                if ($tipo_ben_id === 0) {
                    // tentar por nome/alias; usa o alias mais longo que dê match no início do texto
                    $melhor = null; // ['len'=>, 'tb'=>, 'alias'=>]
                    foreach ($tipos_aliases as $tb) {
                        foreach ($tb['aliases'] as $alias_norm) {
                            if ($alias_norm !== '' && strpos($texto_norm, $alias_norm) === 0) {
                                $len = strlen($alias_norm);
                                if ($melhor === null || $len > $melhor['len']) {
                                    $melhor = ['len' => $len, 'tb' => $tb, 'alias' => $alias_norm];
                                }
                            }
                        }
                    }
                    if ($melhor) {
                        $tipo_ben_id = (int)$melhor['tb']['id'];
                        $tipo_ben_codigo = (int)$melhor['tb']['codigo'];
                        $tipo_bem_desc = (string)$melhor['tb']['descricao'];
                        // remove apenas uma ocorrência inicial do alias do texto original (tolerante a variações de caixa/acentos)
                        $alias_regex = '/^\s*' . preg_quote($melhor['alias'], '/') . '\s*[\-–—:]?\s*/i';
                        // como $melhor['alias'] está normalizado, aplicamos no texto normalizado para encontrar o deslocamento,
                        // mas removemos no texto original de forma simples: tirando o mesmo número de caracteres aproximados.
                        $texto = preg_replace($alias_regex, '', $texto);
                        $texto_norm = $normaliza($texto);
                    }
                }

                // 3) Extrair BEN e COMPLEMENTO do restante
                // BEN deve ser um dos aliases do tipo de bem identificado
                // COMPLEMENTO é o que vem após " - "
                $ben = '';
                $complemento_limpo = trim($texto);

                // Se identificamos um tipo de bem, procurar qual alias aparece no início do texto restante
                if ($tipo_ben_id > 0 && isset($tipos_aliases)) {
                    $tipo_atual = null;
                    foreach ($tipos_aliases as $tb) {
                        if ((int)$tb['id'] === $tipo_ben_id) {
                            $tipo_atual = $tb;
                            break;
                        }
                    }

                    if ($tipo_atual) {
                        // Procurar qual alias do tipo aparece no início do texto
                        $texto_norm_rest = $normaliza($complemento_limpo);
                        $ben_encontrado = null;
                        $melhor_len = 0;

                        foreach ($tipo_atual['aliases'] as $alias_norm) {
                            if ($alias_norm !== '' && strpos($texto_norm_rest, $alias_norm) === 0) {
                                $len = strlen($alias_norm);
                                if ($len > $melhor_len) {
                                    $melhor_len = $len;
                                    // Encontrar a versão original do alias no texto (preservando caixa)
                                    $ben_encontrado = substr($complemento_limpo, 0, strlen($alias_norm));
                                }
                            }
                        }

                        if ($ben_encontrado !== null) {
                            $ben = trim($ben_encontrado);
                            // Remover o BEN do início do texto e o que sobrar é o complemento
                            $resto = substr($complemento_limpo, strlen($ben_encontrado));
                            // Remover separadores iniciais como " - ", " / ", etc
                            $resto = preg_replace('/^\s*[\-–—\/]\s*/u', '', $resto);
                            $complemento_limpo = trim($resto);
                        }
                    }
                }

                // Se não encontrou BEN pelo tipo, usar lógica antiga de separação por " - "
                if ($ben === '') {
                    if (preg_match('/\s\-\s/u', $complemento_limpo)) {
                        [$parte_ben, $parte_comp] = preg_split('/\s\-\s/u', $complemento_limpo, 2);
                        $ben = trim($parte_ben);
                        $complemento_limpo = trim($parte_comp);
                    } else {
                        // se houver múltiplos itens separados por '/', mantemos apenas o primeiro como BEN
                        if (strpos($complemento_limpo, '/') !== false) {
                            $tokens = array_filter(array_map('trim', explode('/', $complemento_limpo)));
                            if (!empty($tokens)) {
                                $ben = array_shift($tokens);
                                $complemento_limpo = trim(implode(' / ', $tokens));
                            }
                        } else {
                            $ben = $complemento_limpo;
                            $complemento_limpo = '';
                        }
                    }
                }

                // Normalizar espaços e caixa para persistência (BEN e complemento em Maiúsculas como padrão)
                $ben = strtoupper(preg_replace('/\s+/', ' ', $ben));
                $complemento_limpo = strtoupper(preg_replace('/\s+/', ' ', $complemento_limpo));

                // Fallback: se ficou tudo vazio tenta usar original
                if ($ben === '' && $complemento_limpo === '') {
                    $ben = strtoupper(trim($complemento_original));
                    if ($ben === '') { $ben = 'SEM DESCRICAO'; }
                }

                // 4) Encontrar dependência por descrição (case- e accent-insensitive)
                $dependencia_id = 0;
                $dep_key = $normaliza($dependencia_original);
                if ($dep_key !== '') {
                    foreach ($dep_map as $d) {
                        if ($d['k'] === $dep_key) {
                            $dependencia_id = $d['id'];
                            break;
                        }
                    }
                }
                $dependencia_rotulo = '';
                if ($dependencia_id > 0) {
                    foreach ($dep_map as $d) {
                        if ($d['id'] === $dependencia_id) { $dependencia_rotulo = $d['descricao']; break; }
                    }
                } else {
                    $dependencia_rotulo = $dependencia_original;
                }

                // 5) Montar a descricao completa: 1x [CODIGO - DESCRICAO] BEN - COMPLEMENTO - (DEPENDENCIA)
                $brackets = '';
                if ($tipo_ben_id > 0) {
                    $brackets = sprintf('%d - %s', (int)$tipo_ben_codigo, strtoupper($tipo_bem_desc));
                } else {
                    $brackets = '?';
                }
                $descricao_completa_calc = '1x [' . $brackets . '] ' . ($ben !== '' ? $ben : 'SEM DESCRICAO');
                if ($complemento_limpo !== '') { $descricao_completa_calc .= ' - ' . $complemento_limpo; }
                if (trim($dependencia_rotulo) !== '') { $descricao_completa_calc .= ' - (' . strtoupper($dependencia_rotulo) . ')'; }

                $sql_produto = "INSERT INTO produtos (planilha_id, codigo, descricao_completa, editado_descricao_completa, tipo_ben_id, editado_tipo_ben_id, ben, editado_ben, complemento, editado_complemento, dependencia_id, editado_dependencia_id, checado, editado, imprimir_etiqueta, imprimir_14_1, observacao, ativo) 
                               VALUES (:planilha_id, :codigo, :descricao_completa, '', :tipo_ben_id, 0, :ben, '', :complemento, '', :dependencia_id, 0, 0, 0, 0, 0, '', 1)";
                $stmt_prod = $conexao->prepare($sql_produto);
                $stmt_prod->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
                $stmt_prod->bindValue(':codigo', $codigo);
                $stmt_prod->bindValue(':descricao_completa', $descricao_completa_calc);
                $stmt_prod->bindValue(':tipo_ben_id', $tipo_ben_id, PDO::PARAM_INT);
                $stmt_prod->bindValue(':ben', $ben);
                $stmt_prod->bindValue(':complemento', $complemento_limpo);
                $stmt_prod->bindValue(':dependencia_id', $dependencia_id, PDO::PARAM_INT);
                if ($stmt_prod->execute()) {
                    $registros_importados++;
                } else {
                    $registros_erros++;
                    $err = $stmt_prod->errorInfo();
                    error_log('ERRO INSERT PRODUTO: ' . json_encode($err));
                }
            } catch (Exception $e) {
                $registros_erros++;
                error_log("Erro linha $linha_atual: " . $e->getMessage());
            }
        }

        // Validar se todos os candidatos foram importados; se não, cancelar a planilha
        if ($registros_importados === $registros_candidatos) {
            $conexao->commit();
            $mensagem = "Importação concluída! {$registros_importados} de {$registros_candidatos} produtos importados.";
            $tipo_mensagem = 'success';
            $sucesso = true;
        } else {
            if ($conexao->inTransaction()) { $conexao->rollBack(); }
            $mensagem = "Importação cancelada: apenas {$registros_importados} de {$registros_candidatos} produtos foram importados. A planilha não foi salva. Os dados do Comum foram salvos.";
            $tipo_mensagem = 'danger';
            $sucesso = false;
        }

    } catch (Exception $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $mensagem = "Erro: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO IMPORTACAO: " . $e->getMessage());
    }

    // Redirecionar com mensagem (sempre voltar para listagem de Comuns)
    if ($sucesso) {
        $_SESSION['mensagem'] = $mensagem;
        $_SESSION['tipo_mensagem'] = 'success';
    } else {
        $_SESSION['mensagem'] = $mensagem ?: 'Não foi possível concluir a importação.';
        $_SESSION['tipo_mensagem'] = 'danger';
    }
    header('Location: ../../index.php');
    exit;
}

// Se for GET, redirecionar de volta
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../index.php');
    exit;
}
?>
