<?php
// upload.php — Importador CSV específico para o formato do relatório
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PlanilhaProcessor.php';

// ======== CONFIGS ESPECÍFICAS PARA SEU CSV ========
$INPUT_NAME = 'planilha';     // <input type="file" name="planilha">
$DELIM = ';';                 // CSV pt-BR padrão
$ENC_IN = 'ISO-8859-1';       // Encoding Latin-1
$TABLE_NAME = 'planilha';     // Nome da tabela no banco

// Mapeamento de colunas baseado na estrutura do seu CSV (0-based)
$MAP = [
    'codigo'            => 0,   // Coluna 0: "09-0040 / 000001"
    'nome'              => 1,   // Coluna 1: "60 - CONSTRUÇÃO EDIFICACAO..."
    'fornecedor'        => 2,   // Coluna 2: "NC"
    'localidade'        => 3,   // Coluna 3: "BR 09-0040"
    'conta'             => 4,   // Coluna 4: "1100"
    'numero_documento'  => 5,   // Coluna 5: "0"
    'dependencia'       => 6,   // Coluna 6: "TEMPLO"
    'data_aquisicao'    => 7,   // Coluna 7: "31/12/2006"
    'valor_aquisicao'   => 8,   // Coluna 8: "4.549,94"
    'valor_depreciacao' => 9,   // Coluna 9: "1.897,02"
    'valor_atual'       => 10,  // Coluna 10: "2.652,92"
    'status'            => 11,  // Coluna 11: "Ativo"
];

// ======== Funções utilitárias ========
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
    $v = str_replace('.', '', $v);   // remove separador de milhar
    $v = str_replace(',', '.', $v);  // vírgula → ponto
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

// ======== Validação do upload ========
if (!isset($_FILES[$INPUT_NAME]) || !is_uploaded_file($_FILES[$INPUT_NAME]['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Arquivo CSV não recebido.']);
    exit;
}

$CSV_PATH = $_FILES[$INPUT_NAME]['tmp_name'];

// ======== Abre CSV ========
$fh = @fopen($CSV_PATH, 'r');
if (!$fh) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível abrir o CSV.']);
    exit;
}

// ======== DB e Processor ========
$db = new Database();
$pdo = $db->getConnection();
$processor = new PlanilhaProcessor($pdo, $TABLE_NAME);

// ======== Loop de leitura ========
$linhas = 0;
$erros = 0;
$puladas = 0;
$rownum = 0;

// Limpa a tabela antes de importar (cuidado!)
$processor->truncateTabela();

while (($cols = fgetcsv($fh, 0, $DELIM)) !== false) {
    $rownum++;

    // Pula linhas com poucas colunas (provavelmente cabeçalho/rodapé)
    if (count($cols) < 5) {
        $puladas++;
        continue;
    }

    // Normaliza encoding e trim
    foreach ($cols as $i => $val) {
        $cols[$i] = sanitize(toUtf8($val, $ENC_IN));
    }

    // Extrai campos pelo mapa
    $codigo = rowGet($cols, $MAP, 'codigo');
    
    // Heurística para identificar linhas de dados: código começa com "09-0040"
    if (!$codigo || strpos($codigo, '09-0040') !== 0) {
        $puladas++;
        continue;
    }

    // Extrai os demais campos
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

// ======== Resultado ========
echo json_encode([
    'success' => $erros === 0,
    'message' => "CSV importado: {$linhas} inseridas; {$puladas} puladas; {$erros} erros.",
]);