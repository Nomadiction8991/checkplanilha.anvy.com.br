<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PlanilhaProcessor.php';

$INPUT_NAME = 'planilha';
$DELIM = ';';
$ENC_IN = 'ISO-8859-1';
$TABLE_NAME = 'planilha';

// Mapeamento CORRIGIDO baseado na estrutura real do seu CSV
$MAP = [
    'codigo'            => 0,
    'nome'              => 1,
    'fornecedor'        => 2,
    'localidade'        => 3,
    'conta'             => 4,
    'numero_documento'  => 5,
    'dependencia'       => 6,
    'data_aquisicao'    => 7,
    'valor_aquisicao'   => 8,
    'valor_depreciacao' => 9,
    'valor_atual'       => 10,
    'status'            => 11,
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
    return ($v === '' || strtolower($v) === 'nan') ? null : $v;
}

function toDecimalPtBr($v) {
    $v = sanitize($v);
    if ($v === null) return null;
    $v = str_replace('.', '', $v);
    $v = str_replace(',', '.', $v);
    return is_numeric($v) ? (float)$v : null;
}

function toDateYmd($v) {
    $v = sanitize($v);
    if ($v === null) return null;
    $v = str_replace(['\\', '.'], '/', $v);
    $parts = preg_split('#[/-]#', $v);
    if (count($parts) === 3) {
        [$d, $m, $y] = $parts;
        if (strlen($y) === 2) $y = ($y > 50 ? "19$y" : "20$y");
        if (checkdate((int)$m, (int)$d, (int)$y)) {
            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }
    }
    return null;
}

function rowGet($cols, $map, $key) {
    $idx = $map[$key] ?? null;
    return is_int($idx) ? ($cols[$idx] ?? null) : null;
}

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

$db = new Database();
$pdo = $db->getConnection();
$processor = new PlanilhaProcessor($pdo, $TABLE_NAME);

$linhas = 0;
$erros = 0;
$puladas = 0;
$rownum = 0;

// Limpa a tabela antes de importar
$processor->truncateTabela();

while (($cols = fgetcsv($fh, 0, $DELIM)) !== false) {
    $rownum++;

    if (count($cols) < 5) {
        $puladas++;
        continue;
    }

    foreach ($cols as $i => $val) {
        $cols[$i] = sanitize(toUtf8($val, $ENC_IN));
    }

    $codigo = rowGet($cols, $MAP, 'codigo');
    
    if (!$codigo || strpos($codigo, '09-0040') !== 0) {
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
    } catch (Throwable $e) {
        $erros++;
        error_log("Erro na linha {$rownum}: " . $e->getMessage());
    }
}

fclose($fh);

echo json_encode([
    'success' => $erros === 0,
    'message' => "Importação concluída: {$linhas} itens importados, {$puladas} linhas puladas, {$erros} erros.",
]);
?>