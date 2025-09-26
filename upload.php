<?php
// Configuração do cabeçalho para resposta JSON
header('Content-Type: application/json; charset=utf-8');

// Inclusão dos arquivos necessários
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PlanilhaProcessor.php';

// Constantes de configuração do sistema
$INPUT_NAME = 'planilha';      // Nome do campo do formulário de upload
$DELIM = ';';                  // Delimitador do CSV (ponto e vírgula)
$ENC_IN = 'ISO-8859-1';        // Codificação original do arquivo (Latin1)
$TABLE_NAME = 'planilha';      // Nome da tabela no banco de dados

// MAPEAMENTO DAS COLUNAS DO CSV - Índices baseados na estrutura do arquivo original
// Cada chave representa um campo da tabela e o valor é a posição da coluna no CSV (começando em 0)
$MAP = [
    'codigo'            => 0,  // Coluna A - Código único do bem
    'nome'              => 3,  // Coluna D - Nome/descrição do bem
    'fornecedor'        => 6,  // Coluna G - Nome do fornecedor
    'localidade'        => 10, // Coluna K - Localização física do bem
    'conta'             => 11, // Coluna L - Conta contábil
    'numero_documento'  => 13, // Coluna N - Número do documento fiscal
    'dependencia'       => 15, // Coluna P - Dependência/Setor
    'data_aquisicao'    => 19, // Coluna T - Data de aquisição (formato variável)
    'valor_aquisicao'   => 21, // Coluna V - Valor original de aquisição
    'valor_depreciacao' => 22, // Coluna W - Valor acumulado da depreciação
    'valor_atual'       => 27, // Coluna AB - Valor atual do bem
    'status'            => 31, // Coluna AF - Situação do bem (Ativo, Baixado, etc.)
];

/**
 * Converte texto para UTF-8 se necessário
 * @param string $s Texto a ser convertido
 * @param string $encIn Codificação de origem
 * @return string Texto em UTF-8
 */
function toUtf8($s, $encIn) {
    if ($s === null || $s === '') return null;
    // Verifica se já está em UTF-8, se não, converte
    if (!mb_detect_encoding($s, 'UTF-8', true)) {
        return mb_convert_encoding($s, 'UTF-8', $encIn);
    }
    return $s;
}

/**
 * Limpa e sanitiza valores
 * Remove espaços, trata valores nulos e inválidos
 * @param mixed $v Valor a ser sanitizado
 * @return mixed Valor sanitizado ou null
 */
function sanitize($v) {
    $v = is_null($v) ? null : trim((string)$v);
    // Converte valores vazios ou inválidos para null
    return ($v === '' || strtolower($v) === 'nan' || strtolower($v) === 'null') ? null : $v;
}

/**
 * Converte valores monetários para formato decimal padrão
 * Trata formatos brasileiro (1.234,56) e internacional (1,234.56)
 * @param string $v Valor monetário em texto
 * @return float|null Valor decimal ou null se inválido
 */
function toDecimalPtBr($v) {
    $v = sanitize($v);
    if ($v === null) return null;
    
    // Remove símbolos de moeda, espaços e caracteres não numéricos
    $v = preg_replace('/[^\d,.-]/', '', $v);
    
    // Formato brasileiro: 1.234,56 → 1234.56
    if (preg_match('/^\d{1,3}(?:\.\d{3})*,\d{2}$/', $v)) {
        $v = str_replace('.', '', $v);  // Remove pontos de milhar
        $v = str_replace(',', '.', $v); // Substitui vírgula por ponto decimal
    }
    // Formato internacional: 1,234.56 → 1234.56
    elseif (preg_match('/^\d{1,3}(?:,\d{3})*\.\d{2}$/', $v)) {
        $v = str_replace(',', '', $v);  // Remove vírgulas de milhar
    }
    
    // Retorna como float se for numérico, caso contrário null
    return is_numeric($v) ? (float)$v : null;
}

/**
 * Converte datas em vários formatos para padrão MySQL (YYYY-MM-DD)
 * Suporta: DD/MM/AAAA, DD-MM-AAAA, AAAA-MM-DD, DD/MM/AA
 * @param string $v Data em texto
 * @return string|null Data no formato YYYY-MM-DD ou null se inválida
 */
function toDateYmd($v) {
    $v = sanitize($v);
    if ($v === null) return null;
    
    // Padrões de data suportados com expressões regulares
    $formats = [
        'd/m/Y' => '(\d{1,2})/(\d{1,2})/(\d{4})',  // 31/12/2023
        'd-m-Y' => '(\d{1,2})-(\d{1,2})-(\d{4})',  // 31-12-2023
        'Y-m-d' => '(\d{4})-(\d{1,2})-(\d{1,2})',  // 2023-12-31
        'd/m/y' => '(\d{1,2})/(\d{1,2})/(\d{2})',  // 31/12/23 (converte para 2023)
    ];
    
    // Tenta cada formato até encontrar um que corresponda
    foreach ($formats as $format => $pattern) {
        if (preg_match('/^' . $pattern . '$/', $v, $matches)) {
            // Extrai dia, mês e ano conforme o formato
            if ($format === 'd/m/Y' || $format === 'd-m-Y') {
                list($d, $m, $y) = array_slice($matches, 1);
            } elseif ($format === 'Y-m-d') {
                list($y, $m, $d) = array_slice($matches, 1);
            } elseif ($format === 'd/m/y') {
                list($d, $m, $y) = array_slice($matches, 1);
                $y = ($y > 50 ? "19$y" : "20$y"); // Converte ano de 2 para 4 dígitos
            }
            
            // Valida se a data é real (ex: não permite 31/02/2023)
            if (checkdate((int)$m, (int)$d, (int)$y)) {
                return sprintf('%04d-%02d-%02d', $y, $m, $d); // Formato MySQL
            }
        }
    }
    
    return null; // Data inválida ou formato não reconhecido
}

/**
 * Obtém valor de uma coluna específica baseado no mapeamento
 * @param array $cols Array com todas as colunas da linha
 * @param array $map Mapeamento de campos para índices
 * @param string $key Chave do campo desejado
 * @return mixed Valor da coluna ou null se não existir
 */
function rowGet($cols, $map, $key) {
    $idx = $map[$key] ?? null;
    return is_int($idx) && isset($cols[$idx]) ? $cols[$idx] : null;
}

// VALIDAÇÃO INICIAL: Verifica se o arquivo foi recebido corretamente
if (!isset($_FILES[$INPUT_NAME]) || !is_uploaded_file($_FILES[$INPUT_NAME]['tmp_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Arquivo CSV não recebido.']);
    exit;
}

// Abre o arquivo CSV para leitura
$CSV_PATH = $_FILES[$INPUT_NAME]['tmp_name'];
$fh = @fopen($CSV_PATH, 'r');
if (!$fh) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível abrir o CSV.']);
    exit;
}

try {
    // Conexão com o banco de dados e inicialização do processador
    $db = new Database();
    $pdo = $db->getConnection();
    $processor = new PlanilhaProcessor($pdo, $TABLE_NAME);
    
    // Limpa a tabela antes da importação (sobrescreve dados existentes)
    $processor->truncateTabela();
    
    // Contadores para estatísticas do processo
    $linhas = 0;    // Linhas importadas com sucesso
    $erros = 0;     // Linhas com erro na importação
    $puladas = 0;   // Linhas ignoradas (código vazio ou colunas insuficientes)
    $rownum = 0;    // Número da linha atual (para controle)

    // Pula o cabeçalho do CSV (primeira linha)
    $header = fgetcsv($fh, 0, $DELIM);
    $rownum++;

    // PROCESSAMENTO LINHA POR LINHA do arquivo CSV
    while (($cols = fgetcsv($fh, 0, $DELIM)) !== false) {
        $rownum++;

        // Verifica se a linha tem colunas suficientes (baseado no mapeamento máximo)
        if (count($cols) < 32) {
            $puladas++;
            continue; // Pula para próxima linha
        }

        // CONVERSÃO E SANITIZAÇÃO: Converte cada valor para UTF-8 e remove espaços
        foreach ($cols as $i => $val) {
            $cols[$i] = sanitize(toUtf8($val, $ENC_IN));
        }

        // Obtém o código do bem (campo obrigatório para importação)
        $codigo = rowGet($cols, $MAP, 'codigo');
        
        // Valida se o código existe e não está vazio
        if (!$codigo) {
            $puladas++;
            continue; // Pula linha se código for inválido
        }

        // EXTRAÇÃO DOS DADOS: Obtém cada campo conforme mapeamento
        $nome = rowGet($cols, $MAP, 'nome');
        $fornecedor = rowGet($cols, $MAP, 'fornecedor');
        $localidade = rowGet($cols, $MAP, 'localidade');
        $conta = rowGet($cols, $MAP, 'conta');
        $numdoc = rowGet($cols, $MAP, 'numero_documento');
        $dependencia = rowGet($cols, $MAP, 'dependencia');
        $dtAq = toDateYmd(rowGet($cols, $MAP, 'data_aquisicao'));        // Converte data
        $valAq = toDecimalPtBr(rowGet($cols, $MAP, 'valor_aquisicao'));   // Converte valor
        $valDep = toDecimalPtBr(rowGet($cols, $MAP, 'valor_depreciacao'));
        $valAtual = toDecimalPtBr(rowGet($cols, $MAP, 'valor_atual'));
        $status = rowGet($cols, $MAP, 'status');

        // Tenta inserir a linha no banco de dados
        try {
            $ok = $processor->inserirLinha(
                $codigo, $nome, $fornecedor, $localidade, $conta, $numdoc,
                $dependencia, $dtAq, $valAq, $valDep, $valAtual, $status
            );
            
            if ($ok) {
                $linhas++; // Contabiliza sucesso
            } else {
                $erros++;  // Contabiliza erro silencioso
            }
        } catch (Exception $e) {
            $erros++; // Contabiliza erro com exceção
        }
    }

    // Fecha o arquivo após processamento
    fclose($fh);

    // RESPOSTA FINAL: Retorna estatísticas do processamento
    echo json_encode([
        'success' => $erros === 0, // Sucesso total se não houve erros
        'message' => "Importação concluída: $linhas itens importados, $puladas linhas puladas, $erros erros."
    ]);

} catch (Exception $e) {
    // TRATAMENTO DE ERRO GLOBAL: Captura exceções não tratadas
    if (isset($fh) && $fh) fclose($fh); // Garante fechamento do arquivo
    
    // Retorna mensagem de erro genérica para o usuário
    echo json_encode([
        'success' => false,
        'message' => 'Erro durante a importação: ' . $e->getMessage()
    ]);
}
?>