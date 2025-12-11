<?php
require_once __DIR__ . '/../../auth.php';
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/functions/comum_functions.php';
// Parser modular
require_once __DIR__ . '/../../app/functions/produto_parser.php';
// Configuração do parser (formato, sinônimos, etc.)
$pp_config = require __DIR__ . '/../../app/config/produto_parser_config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\String\UnicodeString;

// Redirecionamento após sucesso
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $arquivo_csv = $_FILES['arquivo_csv'] ?? null;
    $posicao_data = trim($_POST['posicao_data'] ?? 'D13');
    $pulo_linhas = (int)($_POST['pulo_linhas'] ?? 25);
    $coluna_localidade = strtoupper(trim($_POST['coluna_localidade'] ?? 'K'));
    $posicao_comum = $coluna_localidade; // armazenamos a coluna de localidade como referencia de comum
    $posicao_cnpj = 'N/A';
    $mapeamento_codigo = strtoupper(trim($_POST['mapeamento_codigo'] ?? 'A'));
    $mapeamento_complemento = strtoupper(trim($_POST['mapeamento_complemento'] ?? 'D'));
    $mapeamento_dependencia = strtoupper(trim($_POST['mapeamento_dependencia'] ?? 'P'));
    // Flag opcional para log detalhado de parsing
    $debug_import = isset($_POST['debug_import']);
    $debug_lines = [];

    $mensagem = '';
    $tipo_mensagem = '';
    $sucesso = false;

    try {
        // Validações
        if (!$arquivo_csv || $arquivo_csv['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Selecione um arquivo CSV válido.');
        }
        $extensao = strtolower(pathinfo($arquivo_csv['name'], PATHINFO_EXTENSION));
        if ($extensao !== 'csv') {
            throw new Exception('Apenas arquivos CSV são permitidos.');
        }

        // Carregar arquivo
        $planilha = IOFactory::load($arquivo_csv['tmp_name']);
        $aba = $planilha->getActiveSheet();

        // Obter valores das celulas
        $valor_data = trim($aba->getCell($posicao_data)->getCalculatedValue());

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

        // Carregar todas as linhas e contar candidatas (linhas de produto com código preenchido)
        $linhas = $aba->toArray();
        $linha_atual = 0;
        $registros_candidatos = 0;
        $dependencias_unicas = [];

        // Mapeamento de colunas usando funcao do parser
        $idx_codigo = pp_colunaParaIndice($mapeamento_codigo);
        $idx_complemento = pp_colunaParaIndice($mapeamento_complemento);
        $idx_dependencia = pp_colunaParaIndice($mapeamento_dependencia);
        $idx_localidade = pp_colunaParaIndice($coluna_localidade);

        $codigo_localidade = null;
        $localidades_unicas = [];

        foreach ($linhas as $linha) {
            $linha_atual++;
            if ($linha_atual <= $pulo_linhas) { continue; }
            if (empty(array_filter($linha))) { continue; }
            $codigo_tmp = isset($linha[$idx_codigo]) ? trim((string)$linha[$idx_codigo]) : '';
            if ($codigo_tmp !== '') { $registros_candidatos++; }

            if (isset($linha[$idx_dependencia])) {
                $dep_raw = trim((string)$linha[$idx_dependencia]);
                $dep_norm = pp_normaliza($dep_raw);
                if ($dep_norm !== '' && !array_key_exists($dep_norm, $dependencias_unicas)) {
                    $dependencias_unicas[$dep_norm] = $dep_raw;
                }
            }

            if (isset($linha[$idx_localidade])) {
                $localidade_raw = (string)$linha[$idx_localidade];
                $localidade_num = preg_replace('/\D+/', '', $localidade_raw);
                if ($localidade_num !== '') {
                    $codigo_localidade = (int)$localidade_num;
                    if (!in_array($codigo_localidade, $localidades_unicas, true)) {
                        $localidades_unicas[] = $codigo_localidade;
                    }
                }
            }
        }

        if ($registros_candidatos === 0) {
            throw new Exception('Nenhuma linha de produto encontrada apos o cabecalho. Verifique o mapeamento de colunas e o numero de linhas a pular.');
        }

        if (empty($localidades_unicas)) {
            throw new Exception('Nenhum codigo de localidade encontrado na coluna ' . $coluna_localidade . '.');
        }

        // Garantir cadastro das dependencias distintas encontradas (apenas descricao)
        foreach ($dependencias_unicas as $dep_desc) {
            try {
                $stmtDep = $conexao->prepare("SELECT id FROM dependencias WHERE descricao = :descricao");
                $stmtDep->bindValue(':descricao', $dep_desc);
                $stmtDep->execute();
                $existeDep = $stmtDep->fetch(PDO::FETCH_ASSOC);
                if (!$existeDep) {
                    $stmtInsertDep = $conexao->prepare("INSERT INTO dependencias (descricao) VALUES (:descricao)");
                    $stmtInsertDep->bindValue(':descricao', $dep_desc);
                    $stmtInsertDep->execute();
                }
            } catch (Throwable $e) {
                // ignora duplicidade/erro e segue
            }
        }

        // Garantir cadastro/uso de todas as localidades encontradas
        $comum_processado_id = null;
        foreach ($localidades_unicas as $codLoc) {
            try {
                $stmtBuscaComum = $conexao->prepare("SELECT id FROM comums WHERE codigo = :codigo");
                $stmtBuscaComum->bindValue(':codigo', $codLoc, PDO::PARAM_INT);
                $stmtBuscaComum->execute();
                $comumEncontrado = $stmtBuscaComum->fetch(PDO::FETCH_ASSOC);

                if ($comumEncontrado) {
                    if ($comum_processado_id === null) {
                        $comum_processado_id = (int)$comumEncontrado['id'];
                    }
                    $comuns_existentes++;
                } else {
                    $novoId = garantir_comum_por_codigo($conexao, $codLoc);
                    if ($comum_processado_id === null) {
                        $comum_processado_id = (int)$novoId;
                    }
                    $comuns_cadastradas++;
                }
            } catch (Throwable $e) {
                $comuns_falha[] = $codLoc;
            }
        }

        if (empty($comum_processado_id)) {
            throw new Exception('Nenhum comum valido encontrado ou criado a partir da coluna de localidade.');
        }

        // Iniciar transacao apenas para planilha+produtos; dados do Comum ja foram persistidos
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
        $stmt->bindValue(':mapeamento_colunas', "codigo=$mapeamento_codigo;complemento=$mapeamento_complemento;dependencia=$mapeamento_dependencia;localidade=$coluna_localidade");
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

        // Construir aliases dos tipos via módulo parser
        $tipos_aliases = pp_construir_aliases_tipos($tipos_bens);

        // Construir chaves normalizadas para dependências
        foreach ($dep_map as &$dep) {
            $dep['k'] = pp_normaliza($dep['descricao']);
        }
        unset($dep);

        // Processar linhas do CSV
        $registros_importados = 0;
        $registros_erros = 0;
        $linha_atual = 0;
        // Sequencial global para respeitar a PK (id_produto é único na tabela)
        $stmtMaxId = $conexao->query("SELECT COALESCE(MAX(id_produto), 0) AS max_id FROM produtos");
        $id_produto_sequencial = (int)($stmtMaxId->fetchColumn() ?? 0) + 1;
        $erros_produtos = []; // Para coletar erros específicos

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
                // Texto base para parsing: extrair BEN do complemento
                $texto_base = $complemento_original;
                // 1) Remover prefixo de código (ex: "68 - ")
                [$codigo_detectado, $texto_sem_prefixo] = pp_extrair_codigo_prefixo($texto_base);
                // 2) Detectar tipo (por código ou alias) mantendo texto original intacto
                [$tipo_detectado, $texto_pos_tipo] = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
                $tipo_bem_id = (int)$tipo_detectado['id'];
                $tipo_bem_codigo = $tipo_detectado['codigo'];
                $tipo_bem_desc = $tipo_detectado['descricao'];
                
                // 3) Extrair BEM e COMPLEMENTO usando aliases do tipo (se disponível)
                $aliases_tipo_atual = null;
                $aliases_originais = null;
                if ($tipo_bem_id) {
                    foreach ($tipos_aliases as $tbTmp) { 
                        if ($tbTmp['id'] === $tipo_bem_id) { 
                            $aliases_tipo_atual = $tbTmp['aliases'];
                            $aliases_originais = $tbTmp['aliases_originais'] ?? null;
                            break; 
                        } 
                    }
                }
                
                [$ben_raw, $comp_raw] = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual ?: [], $aliases_originais, $tipo_bem_desc);
                $ben = strtoupper(preg_replace('/\s+/', ' ', trim($ben_raw)));
                $complemento_limpo = strtoupper(preg_replace('/\s+/', ' ', trim($comp_raw)));
                
                // Validação: BEM deve ser um dos aliases do tipo (com fuzzy match)
                $ben_valido = false;
                if ($ben !== '' && $tipo_bem_id > 0 && $aliases_tipo_atual) {
                    $ben_norm = pp_normaliza($ben);
                    foreach ($aliases_tipo_atual as $alias_norm) {
                        if ($alias_norm === $ben_norm || pp_match_fuzzy($ben, $alias_norm)) {
                            $ben_valido = true;
                            break;
                        }
                    }
                }
                
                // Se BEM inválido ou vazio, tentar forçar para primeiro alias do tipo
                if (!$ben_valido && $tipo_bem_id > 0 && !empty($aliases_tipo_atual)) {
                    // Pegar primeiro alias válido do tipo
                    foreach ($aliases_tipo_atual as $alias_norm) {
                        if ($alias_norm !== '') {
                            // Encontrar correspondente em maiúscula da descrição do tipo
                            $tokens = array_map('trim', preg_split('/\s*\/\s*/', $tipo_bem_desc));
                            foreach ($tokens as $tok) {
                                if (pp_normaliza($tok) === $alias_norm) {
                                    $ben = strtoupper($tok);
                                    $ben_valido = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                
                // Fallback completo: se ainda vazio, usar todo texto no complemento
                if ($ben === '' && $complemento_limpo === '') {
                    $complemento_limpo = strtoupper(trim($texto_sem_prefixo));
                    if ($complemento_limpo === '') {
                        $complemento_limpo = strtoupper(trim($complemento_original));
                    }
                }
                
                // Remover redundâncias do BEN no início do complemento
                if ($ben !== '' && $complemento_limpo !== '') {
                    $complemento_limpo = pp_remover_ben_do_complemento($ben, $complemento_limpo);
                }
                // 4) Encontrar dependência
                $dependencia_id = 0;
                $dep_key = pp_normaliza($dependencia_original);
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
                // 5) Montar descrição completa via parser (BEM pode estar vazio ou preenchido)
                $descricao_completa_calc = pp_montar_descricao(1, $tipo_bem_codigo, $tipo_bem_desc, $ben, $complemento_limpo, $dependencia_rotulo, $pp_config);

                // Marcar se houve problema na extração (tipo inválido OU BEM não validado quando tipo existe)
                $tem_erro_parsing = ($tipo_bem_id === 0 && $codigo_detectado !== null) || ($tipo_bem_id > 0 && $ben !== '' && !$ben_valido);
                
                if ($debug_import) {
                    $debug_lines[] = json_encode([
                        'linha' => $linha_atual,
                        'codigo' => $codigo,
                        'tipo_id' => $tipo_bem_id,
                        'tipo_codigo' => $tipo_bem_codigo,
                        'tipo_desc' => $tipo_bem_desc,
                        'ben' => $ben,
                        'ben_valido' => $ben_valido,
                        'complemento' => $complemento_limpo,
                        'dependencia_id' => $dependencia_id,
                        'dependencia' => $dependencia_rotulo,
                        'descricao_final' => $descricao_completa_calc,
                        'erro_parsing' => $tem_erro_parsing
                    ], JSON_UNESCAPED_UNICODE);
                }

                // Inserir produto (usaremos observacao para flag temporária de erro se necessário)
                $obs_prefix = $tem_erro_parsing ? '[REVISAR] ' : '';
                // Inserção agora contempla a coluna 'novo' (0 = importado) e mantém flags iniciais neutras
                $sql_produto = "INSERT INTO produtos (
                               planilha_id, id_produto, codigo, descricao_completa, editado_descricao_completa,
                               tipo_bem_id, editado_tipo_bem_id, bem, editado_bem,
                               complemento, editado_complemento, dependencia_id, editado_dependencia_id,
                               checado, editado, imprimir_etiqueta, imprimir_14_1,
                               observacao, ativo, novo, condicao_14_1,
                               administrador_acessor_id, doador_conjugue_id
                               ) VALUES (
                               :planilha_id, :id_produto, :codigo, :descricao_completa, '',
                               :tipo_bem_id, 0, :bem, '',
                               :complemento, '', :dependencia_id, 0,
                               0, 0, 0, :imprimir_14_1,
                               :observacao, 1, 0, :condicao_14_1,
                               0, 0
                               )";
                $stmt_prod = $conexao->prepare($sql_produto);
                $stmt_prod->bindValue(':planilha_id', $id_planilha, PDO::PARAM_INT);
                $stmt_prod->bindValue(':id_produto', $id_produto_sequencial, PDO::PARAM_INT);
                $stmt_prod->bindValue(':codigo', $codigo);
                $stmt_prod->bindValue(':descricao_completa', $descricao_completa_calc);
                $stmt_prod->bindValue(':tipo_bem_id', $tipo_bem_id, PDO::PARAM_INT);
                $stmt_prod->bindValue(':bem', $ben);
                $stmt_prod->bindValue(':complemento', $complemento_limpo);
                $stmt_prod->bindValue(':dependencia_id', $dependencia_id, PDO::PARAM_INT);
                $stmt_prod->bindValue(':imprimir_14_1', 0, PDO::PARAM_INT);
                $stmt_prod->bindValue(':observacao', $obs_prefix);
                // Condição 14.1 padrão para atender NOT NULL (definida como '2' por padrão)
                $stmt_prod->bindValue(':condicao_14_1', '2');
                if ($stmt_prod->execute()) {
                    $registros_importados++;
                    $id_produto_sequencial++;
                } else {
                    $registros_erros++;
                    $err = $stmt_prod->errorInfo();
                    $erro_msg = "Linha $linha_atual: " . ($err[2] ?? 'Erro desconhecido no INSERT');
                    $erros_produtos[] = $erro_msg;
                    error_log('ERRO INSERT PRODUTO: ' . json_encode($err));
                }
            } catch (Exception $e) {
                $registros_erros++;
                $erro_msg = "Linha $linha_atual: " . $e->getMessage();
                $erros_produtos[] = $erro_msg;
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
            $mensagem_extra = '';
            if ($registros_importados == 0 && !empty($erros_produtos)) {
                $mensagem_extra = ' Erros encontrados: ' . implode('; ', array_slice($erros_produtos, 0, 5)); // Mostra até 5 erros
            }
            $mensagem = "Importação cancelada: apenas {$registros_importados} de {$registros_candidatos} produtos foram importados. A planilha não foi salva. Os dados do Comum foram salvos.{$mensagem_extra}";
            $tipo_mensagem = 'danger';
            $sucesso = false;
        }

        // Persistir log de debug se solicitado
        if ($debug_import && !empty($debug_lines)) {
            $logDir = __DIR__ . '/../../app/tmp';
            if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
            $logFile = $logDir . '/import_debug_' . date('Ymd_His') . '_' . uniqid() . '.log';
            @file_put_contents($logFile, implode(PHP_EOL, $debug_lines));
            $mensagem .= ' [DEBUG salvo em app/tmp/' . basename($logFile) . ']';
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
