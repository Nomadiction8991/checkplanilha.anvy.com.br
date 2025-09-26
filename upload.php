<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PlanilhaProcessor.php';

$INPUT_NAME = 'planilha';
$DELIM = ';';
$ENC_IN = 'ISO-8859-1';
$TABLE_NAME = 'planilha';

// Mapeamento CORRETO conforme fornecido
$MAP = [
    'codigo'            => 0,
    'nome'              => 3,
    'fornecedor'        => 6,
    'localidade'        => 10,
    'conta'             => 11,
    'numero_documento'  => 13,
    'dependencia'       => 15,
    'data_aquisicao'    => 19,
    'valor_aquisicao'   => 21,
    'valor_depreciacao' => 22,
    'valor_atual'       => 27,
    'status'            => 31,
];

function toUtf8($s, $encIn) {
    if ($s === null || $s === '') return null;
    if (!mb_detect_encoding($s, 'UTF-8', true)) {
        return mb_convert_encoding($s, 'UTF-8', $encIn);
    }
    return $s;
}

function sanitize($v) {
    $v = is_null($v) ? null : trim((string)$v);
    return ($v === '' || strtolower($v) === 'nan' || strtolower($v) === 'null') ? null : $v;
}

function toDecimalPtBr($v) {
    $v = sanitize($v);
    if ($v === null) return null;
    
    // Remove possíveis símbolos de moeda e espaços
    $v = preg_replace('/[^\d,.-]/', '', $v);
    
    // Verifica se tem formato brasileiro (1.234,56)
    if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{2}$/', $v)) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    }
    // Verifica se tem formato internacional (1,234.56)
    elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{2}$/', $v)) {
        $v = str_replace(',', '', $v);
    }
    
    return is_numeric($v) ? (float)$v : null;
}

function toDateYmd($v) {
    $v = sanitize($v);
    if ($v === null) return null;
    
    // Tenta vários formatos comuns
    $formats = [
        'd/m/Y' => '(\d{1,2})/(\d{1,2})/(\d{4})',
        'd-m-Y' => '(\d{1,2})-(\d{1,2})-(\d{4})',
        'Y-m-d' => '(\d{4})-(\d{1,2})-(\d{1,2})',
        'd/m/y' => '(\d{1,2})/(\d{1,2})/(\d{2})',
    ];
    
    foreach ($formats as $format => $pattern) {
        if (preg_match('/^' . $pattern . '$/', $v, $matches)) {
            if ($format === 'd/m/Y' || $format === 'd-m-Y') {
                list($d, $m, $y) = array_slice($matches, 1);
            } elseif ($format === 'Y-m-d') {
                list($y, $m, $d) = array_slice($matches, 1);
            } elseif ($format === 'd/m/y') {
                list($d, $m, $y) = array_slice($matches, 1);
                $y = ($y > 50 ? "19$y" : "20$y");
            }
            
            if (checkdate((int)$m, (int)$d, (int)$y)) {
                return sprintf('%04d-%02d-%02d', $y, $m, $d);
            }
        }
    }
    
    return null;
}

function rowGet($cols, $map, $key) {
    $idx = $map[$key] ?? null;
    return is_int($idx) && isset($cols[$idx]) ? $cols[$idx] : null;
}

// Verifica se o arquivo foi enviado
if (!isset($_FILES[$INPUT_NAME]) || !is_uploaded_file($_FILES[$INPUT_NAME]['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Arquivo CSV não recebido.']);
    exit;
}

$CSV_PATH = $_FILES[$INPUT_NAME]['tmp_name'];
$fh = @fopen($CSV_PATH, 'r');
if (!$fh) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível abrir o CSV.']);
    exit;
}

try {
    $db = new Database();
    $pdo = $db->getConnection();
    $processor = new PlanilhaProcessor($pdo, $TABLE_NAME);
    
    // Limpa a tabela antes de importar
    $processor->truncateTabela();
    
    $linhas = 0;
    $erros = 0;
    $puladas = 0;
    $rownum = 0;
    $debug_info = [];

    // Pula o cabeçalho se existir (primeira linha)
    $header = fgetcsv($fh, 0, $DELIM);
    $rownum++;

    while (($cols = fgetcsv($fh, 0, $DELIM)) !== false) {
        $rownum++;
        
        // Log da linha original para debug
        $debug_info[] = "Linha $rownum: " . implode(' | ', $cols);

        if (count($cols) < 32) { // Verifica se tem colunas suficientes
            $puladas++;
            $debug_info[] = "Linha $rownum pulada - poucas colunas: " . count($cols);
            continue;
        }

        // Converte para UTF-8 e sanitiza
        foreach ($cols as $i => $val) {
            $cols[$i] = sanitize(toUtf8($val, $ENC_IN));
        }

        $codigo = rowGet($cols, $MAP, 'codigo');
        
        // Verifica se é um código válido (mais flexível)
        if (!$codigo) {
            $puladas++;
            $debug_info[] = "Linha $rownum pulada - código vazio";
            continue;
        }

        // Extrai os dados usando o mapeamento
        $nome = rowGet($cols, $MAP, 'nome');
        $fornecedor = rowGet($cols, $MAP, 'fornecedor');
        $localidade = rowGet($cols, $MAP, 'localidade');
        $conta = rowGet($cols, $MAP, 'conta');
        $numdoc = rowGet($cols, $MAP, 'numero_documento');
        $dependencia = rowGet($cols, $MAP, 'dependencia');
        $dtAq = toDateYmd(rowGet($cols, $MAP, 'data_aquisicao'));
        $valAq = toDecimalPtBr(rowGet($cols, $MAP, 'valor_aquisicao'));
        $valDep = toDecimalPtBr(rowGet($cols, $MAP, 'valor_depreciacao'));
        $valAtual = toDecimalPtBr(rowGet($cols, $MAP, 'valor_atual'));
        $status = rowGet($cols, $MAP, 'status');

        // Debug dos valores extraídos
        $debug_info[] = "Linha $rownum - Código: $codigo, Nome: $nome, Localidade: $localidade";

        try {
            $ok = $processor->inserirLinha(
                $codigo, $nome, $fornecedor, $localidade, $conta, $numdoc,
                $dependencia, $dtAq, $valAq, $valDep, $valAtual, $status
            );
            
            if ($ok) {
                $linhas++;
                $debug_info[] = "Linha $rownum inserida com sucesso";
            } else {
                $erros++;
                $debug_info[] = "Linha $rownum falhou na inserção";
            }
        } catch (Exception $e) {
            $erros++;
            $debug_info[] = "ERRO Linha $rownum: " . $e->getMessage();
            error_log("Erro na linha {$rownum}: " . $e->getMessage());
        }
    }

    fclose($fh);

    // Log completo do processo
    error_log("IMPORTAÇÃO FINALIZADA: $linhas inseridos, $puladas puladas, $erros erros");
    foreach ($debug_info as $debug_line) {
        error_log("DEBUG: " . $debug_line);
    }

    echo json_encode([
        'success' => $erros === 0,
        'message' => "Importação concluída: $linhas itens importados, $puladas linhas puladas, $erros erros.",
        'debug' => $erros > 0 ? $debug_info : null
    ]);

} catch (Exception $e) {
    if (isset($fh) && $fh) fclose($fh);
    
    error_log("ERRO GRAVE no upload: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro durante a importação: ' . $e->getMessage()
    ]);
}
?>