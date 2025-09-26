<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/planilhaprocessor.php';

$INPUT_NAME = 'planilha';
$DELIM = ';';
$ENC_IN = 'ISO-8859-1';
$TABLE_NAME = 'planilha';

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
    
    $v = preg_replace('/[^\d,.-]/', '', $v);
    
    if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{2}$/', $v)) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    }
    elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{2}$/', $v)) {
        $v = str_replace(',', '', $v);
    }
    
    return is_numeric($v) ? (float)$v : null;
}

function toDateYmd($v) {
    $v = sanitize($v);
    if ($v === null) return null;
    
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
if (!isset($_FILES[$INPUT_NAME]) || $_FILES[$INPUT_NAME]['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Arquivo CSV não recebido ou com erro.']);
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
    
    $processor->truncateTabela();
    
    $linhas = 0;
    $erros = 0;
    $puladas = 0;
    $rownum = 0;

    // Pula o cabeçalho
    $header = fgetcsv($fh, 0, $DELIM);
    $rownum++;

    while (($cols = fgetcsv($fh, 0, $DELIM)) !== false) {
        $rownum++;

        if (count($cols) < 32) {
            $puladas++;
            continue;
        }

        foreach ($cols as $i => $val) {
            $cols[$i] = sanitize(toUtf8($val, $ENC_IN));
        }

        $codigo = rowGet($cols, $MAP, 'codigo');
        
        if (!$codigo) {
            $puladas++;
            continue;
        }

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

        try {
            $ok = $processor->inserirLinha(
                $codigo, $nome, $fornecedor, $localidade, $conta, $numdoc,
                $dependencia, $dtAq, $valAq, $valDep, $valAtual, $status
            );
            
            if ($ok) {
                $linhas++;
            } else {
                $erros++;
            }
        } catch (Exception $e) {
            $erros++;
            // Log em arquivo se necessário
            file_put_contents('upload_errors.json', json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'linha' => $rownum,
                'erro' => $e->getMessage(),
                'codigo' => $codigo
            ]) . "\n", FILE_APPEND);
        }
    }

    fclose($fh);

    echo json_encode([
        'success' => $erros === 0,
        'message' => "Importação concluída: $linhas itens importados, $puladas linhas puladas, $erros erros."
    ]);

} catch (Exception $e) {
    if (isset($fh) && $fh) fclose($fh);
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro durante a importação: ' . $e->getMessage()
    ]);
}
?>