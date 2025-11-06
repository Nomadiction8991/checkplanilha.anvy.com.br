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

        // Mapeamento de colunas usando função do parser
        $idx_codigo = pp_colunaParaIndice($mapeamento_codigo);
        $idx_complemento = pp_colunaParaIndice($mapeamento_complemento);
        $idx_dependencia = pp_colunaParaIndice($mapeamento_dependencia);

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
                // Texto base para parsing
                $texto_base = $complemento_original;
                // 1) Remover prefixo de código
                [$codigo_detectado, $texto_sem_prefixo] = pp_extrair_codigo_prefixo($texto_base);
                // 2) Detectar tipo (por código ou alias) e obter texto restante
                [$tipo_detectado, $texto_pos_tipo] = pp_detectar_tipo($texto_sem_prefixo, $codigo_detectado, $tipos_aliases);
                $tipo_ben_id = (int)$tipo_detectado['id'];
                $tipo_ben_codigo = $tipo_detectado['codigo'];
                $tipo_bem_desc = $tipo_detectado['descricao'];
                // 3) Extrair BEN e COMPLEMENTO usando aliases do tipo (se disponível)
                $aliases_tipo_atual = null;
                if ($tipo_ben_id) {
                    foreach ($tipos_aliases as $tbTmp) { if ($tbTmp['id'] === $tipo_ben_id) { $aliases_tipo_atual = $tbTmp['aliases']; break; } }
                }
                [$ben_raw, $comp_raw] = pp_extrair_ben_complemento($texto_pos_tipo, $aliases_tipo_atual ?: []);
                $ben = strtoupper(preg_replace('/\s+/', ' ', $ben_raw));
                $complemento_limpo = strtoupper(preg_replace('/\s+/', ' ', $comp_raw));
                if ($ben === '' && $complemento_limpo === '') {
                    $ben = strtoupper(trim($complemento_original));
                    if ($ben === '') { $ben = 'SEM DESCRICAO'; }
                }
                // Remover redundâncias do BEN no complemento
                $complemento_limpo = pp_remover_ben_do_complemento($ben, $complemento_limpo);
                // Aplicar sinônimos conforme config
                [$ben, $complemento_limpo] = pp_aplicar_sinonimos($ben, $complemento_limpo, $tipo_bem_desc, $pp_config);
                // Ajustar BEN para ser um alias válido do tipo, se possível
                if ($tipo_ben_id > 0) {
                    $ben = pp_forcar_ben_em_aliases($ben, $tipo_bem_desc, $tipo_detectado['alias_usado'] ?? null);
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
                // 5) Montar descrição completa via parser
                $descricao_completa_calc = pp_montar_descricao(1, $tipo_ben_codigo, $tipo_bem_desc, $ben, $complemento_limpo, $dependencia_rotulo, $pp_config);

                if ($debug_import) {
                    $debug_lines[] = json_encode([
                        'linha' => $linha_atual,
                        'codigo' => $codigo,
                        'tipo_id' => $tipo_ben_id,
                        'tipo_codigo' => $tipo_ben_codigo,
                        'tipo_desc' => $tipo_bem_desc,
                        'ben' => $ben,
                        'complemento' => $complemento_limpo,
                        'dependencia_id' => $dependencia_id,
                        'dependencia' => $dependencia_rotulo,
                        'descricao_final' => $descricao_completa_calc
                    ], JSON_UNESCAPED_UNICODE);
                }

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
