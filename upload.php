<?php
// upload.php — Importador CSV pt-BR (separador ';', vírgula decimal)
// Requer: config.php (Database) e PlanilhaProcessor.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PlanilhaProcessor.php';

// ======== CONFIGS GERAIS ========
$INPUT_NAME = 'arquivo';     // <input type="file" name="arquivo">
$DELIM      = ';';           // CSV pt-BR padrão
$ENC_IN     = 'ISO-8859-1';  // muitos relatórios vêm em Latin-1
$TABLE_NAME = 'planilha';    // altere aqui se sua tabela tiver outro nome

// Mapeamento de colunas (0-based). Ajuste conforme o seu CSV.
// Coloque null quando a coluna não existir no CSV.
$MAP = [
  'codigo'            => 0,   // ex.: "09-0040 / 000044"
  'nome'              => 3,   // ex.: "1 - BANCO DE MADEIRA / ..."
  'fornecedor'        => null,
  'localidade'        => null,
  'conta'             => null,
  'numero_documento'  => null,
  'dependencia'       => 6,   // ex.: "NC"
  'data_aquisicao'    => null,// ex.: 8 (se existir dd/mm/aaaa)
  'valor_aquisicao'   => null,// ex.: 20 (se existir)
  'valor_depreciacao' => null,// ex.: 23 (se existir)
  'valor_atual'       => null,// ex.: 26 (se existir)
  'status'            => 31,  // ex.: "Ativo"
  // Campo extra comum no CSV do relatório (se quiser armazenar em outra tabela/coluna)
  'taxa_depreciacao'  => 27,  // ex.: "0,08"
];

// ======== Funções utilitárias ========
function toUtf8($s, $encIn) {
  if ($s === null) return null;
  if (!mb_detect_encoding($s, 'UTF-8', true)) return mb_convert_encoding($s, 'UTF-8', $encIn);
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
    [$d,$m,$y] = $parts;
    if (strlen($y) === 2) $y = ($y > 50 ? "19$y" : "20$y");
    if (checkdate((int)$m,(int)$d,(int)$y)) {
      return sprintf('%04d-%02d-%02d', $y,$m,$d);
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
  echo json_encode(['success'=>false, 'message'=>'Arquivo CSV não recebido.']);
  exit;
}
$CSV_PATH = $_FILES[$INPUT_NAME]['tmp_name'];

// ======== Abre CSV ========
$fh = @fopen($CSV_PATH, 'r');
if (!$fh) {
  http_response_code(500);
  echo json_encode(['success'=>false, 'message'=>'Não foi possível abrir o CSV.']);
  exit;
}

// ======== DB e Processor ========
$db  = new Database();
$pdo = $db->getConnection();
$processor = new PlanilhaProcessor($pdo, $TABLE_NAME);

// ======== Loop de leitura ========
$linhas  = 0;
$erros   = 0;
$puladas = 0;
$rownum  = 0;

// Se quiser limpar a tabela antes de importar, descomente:
// $processor->truncateTabela();

while (($cols = fgetcsv($fh, 0, $DELIM)) !== false) {
  $rownum++;

  // Normaliza encoding e trim
  foreach ($cols as $i => $val) {
    $cols[$i] = sanitize(toUtf8($val, $ENC_IN));
  }

  // Heurísticas para pular “lixo” de cabeçalho/rodapé/filtros:
  $c0 = $cols[0] ?? null;
  if ($c0 === null) { $puladas++; continue; }
  if (preg_match('/^data posi[cç][aã]o/i', $c0)) { $puladas++; continue; }  // "Data Posição"
  // Filas de filtro com "* Todos *" em qualquer coluna
  $temTodos = false;
  foreach ($cols as $v) {
    if ($v && preg_match('/^\*?\s*todos\s*\*?$/i', $v)) { $temTodos = true; break; }
  }
  if ($temTodos) { $puladas++; continue; }

  // Extrai campos pelo mapa
  $codigo     = rowGet($cols, $MAP, 'codigo');
  $nome       = rowGet($cols, $MAP, 'nome');
  $fornecedor = rowGet($cols, $MAP, 'fornecedor');
  $localidade = rowGet($cols, $MAP, 'localidade');
  $conta      = rowGet($cols, $MAP, 'conta');
  $numdoc     = rowGet($cols, $MAP, 'numero_documento');
  $dependencia= rowGet($cols, $MAP, 'dependencia');
  $dtAq       = toDateYmd(rowGet($cols, $MAP, 'data_aquisicao'));
  $valAq      = toDecimalPtBr(rowGet($cols, $MAP, 'valor_aquisicao'));
  $valDep     = toDecimalPtBr(rowGet($cols, $MAP, 'valor_depreciacao'));
  $valAtual   = toDecimalPtBr(rowGet($cols, $MAP, 'valor_atual'));
  $status     = rowGet($cols, $MAP, 'status');

  // Regras mínimas para considerar linha de item
  if (!$codigo) { $puladas++; continue; }

  try {
    $ok = $processor->inserirLinha(
      $codigo, $nome, $fornecedor, $localidade, $conta, $numdoc,
      $dependencia, $dtAq, $valAq, $valDep, $valAtual, $status
    );
    if ($ok) $linhas++; else $erros++;
  } catch (Throwable $e) {
    $erros++;
    // Descomente para logar: error_log("Erro na linha {$rownum}: ".$e->getMessage());
  }
}
fclose($fh);

// ======== Resultado ========
echo json_encode([
  'success' => $erros === 0,
  'message' => "CSV importado: {$linhas} inseridas; {$puladas} puladas; {$erros} erros.",
]);
