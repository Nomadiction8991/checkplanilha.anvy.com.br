<?php
require_once '../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $linhas_pular = (int)($_POST['linhas_pular'] ?? 25);
    $localizacao_comum = trim($_POST['localizacao_comum'] ?? 'D16');
    $localizacao_data_posicao = trim($_POST['localizacao_data_posicao'] ?? 'D13');
    $localizacao_endereco = trim($_POST['localizacao_endereco'] ?? 'A4');
    $localizacao_cnpj = trim($_POST['localizacao_cnpj'] ?? 'U5');
    // Novo campo: nome e assinatura do responsável (Administrador/Acessor)
    $nome_responsavel = trim($_POST['nome_responsavel'] ?? null);
    $assinatura_responsavel = $_POST['assinatura_responsavel'] ?? null; // data URL base64
    // Administração (estado) e cidade (obrigatórios)
    $administracao = trim($_POST['administracao'] ?? null); // formato SIGLA|ID
    $cidade = trim($_POST['cidade'] ?? null);
    // Setor (opcional, numérico)
    $setor = !empty($_POST['setor']) ? (int)$_POST['setor'] : null;
    
    // Mapeamento simplificado
    $mapeamento = [
        'codigo' => strtoupper($_POST['codigo'] ?? 'A'),
        'nome' => strtoupper($_POST['nome'] ?? 'D'),
        'dependencia' => strtoupper($_POST['dependencia'] ?? 'P'),
    ];

    $mensagem = '';
    $tipo_mensagem = '';

    try {
        if (empty($localizacao_comum)) {
            throw new Exception('A localização da célula comum é obrigatória.');
        }

        if (empty($localizacao_data_posicao)) {
            throw new Exception('A localização da célula data_posicao é obrigatória.');
        }

        if (empty($localizacao_endereco)) {
            throw new Exception('A localização da célula endereço é obrigatória.');
        }

        if (empty($localizacao_cnpj)) {
            throw new Exception('A localização da célula CNPJ é obrigatória.');
        }

        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Selecione um arquivo CSV válido.');
        }

        // Validar mapeamentos obrigatórios (1 a 2 letras A-Z)
        foreach ([
            'codigo' => 'Código',
            'nome' => 'Nome',
            'dependencia' => 'Dependência',
        ] as $k => $rotulo) {
            $val = $mapeamento[$k] ?? '';
            if (empty($val)) {
                throw new Exception("O mapeamento da coluna {$rotulo} é obrigatório.");
            }
            if (!preg_match('/^[A-Z]{1,2}$/', $val)) {
                throw new Exception("O mapeamento da coluna {$rotulo} deve ser 1 ou 2 letras de A a Z.");
            }
        }

        $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
        $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));

        if ($extensao !== 'csv') {
            throw new Exception('Apenas arquivos CSV são permitidos.');
        }

        // Processar o arquivo CSV para obter os valores das células
        $planilha = IOFactory::load($arquivo_tmp);
        $aba_ativa = $planilha->getActiveSheet();
        
        // Obter o valor da célula comum
        $valor_comum = $aba_ativa->getCell($localizacao_comum)->getCalculatedValue();
        
        if (empty($valor_comum)) {
            throw new Exception('A célula ' . $localizacao_comum . ' está vazia no arquivo CSV.');
        }

        // Obter o valor da célula data_posicao
        $valor_data_posicao = $aba_ativa->getCell($localizacao_data_posicao)->getCalculatedValue();
        
        if (empty($valor_data_posicao)) {
            throw new Exception('A célula ' . $localizacao_data_posicao . ' está vazia no arquivo CSV.');
        }

        // Obter o valor da célula endereco
        $valor_endereco = $aba_ativa->getCell($localizacao_endereco)->getCalculatedValue();
        
        if (empty($valor_endereco)) {
            throw new Exception('A célula ' . $localizacao_endereco . ' está vazia no arquivo CSV.');
        }

        // Obter o valor da célula CNPJ e extrair apenas números
        $valor_cnpj = $aba_ativa->getCell($localizacao_cnpj)->getCalculatedValue();
        $cnpj_somente_numeros = preg_replace('/[^0-9]/', '', $valor_cnpj);

        // Converter a data para formato MySQL (YYYY-MM-DD)
        $data_mysql = null;
        if (!empty($valor_data_posicao)) {
            if (is_numeric($valor_data_posicao)) {
                // Se for um número serial do Excel, converter para data
                $data_mysql = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valor_data_posicao)->format('Y-m-d');
            } else {
                // Tentar converter string para data
                $timestamp = strtotime($valor_data_posicao);
                if ($timestamp !== false) {
                    $data_mysql = date('Y-m-d', $timestamp);
                } else {
                    throw new Exception('Formato de data inválido na célula ' . $localizacao_data_posicao . ': ' . $valor_data_posicao);
                }
            }
        }

        // Validações adicionais (campos obrigatórios)
        if (empty($administracao)) {
            throw new Exception('O campo Administração é obrigatório.');
        }
        if (empty($cidade)) {
            throw new Exception('O campo Cidade é obrigatório.');
        }

        // Iniciar transação
        $conexao->beginTransaction();

    // Inserir a planilha na tabela planilhas com os valores obtidos do CSV
    $sql_planilha = "INSERT INTO planilhas (comum, data_posicao, endereco, cnpj, nome_responsavel, administracao, cidade, assinatura_responsavel, setor) VALUES (:comum, :data_posicao, :endereco, :cnpj, :nome_responsavel, :administracao, :cidade, :assinatura_responsavel, :setor)";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':comum', $valor_comum);
    $stmt_planilha->bindValue(':data_posicao', $data_mysql);
    $stmt_planilha->bindValue(':endereco', $valor_endereco);
    $stmt_planilha->bindValue(':cnpj', $cnpj_somente_numeros);
    $stmt_planilha->bindValue(':nome_responsavel', $nome_responsavel);
    $stmt_planilha->bindValue(':administracao', $administracao);
    $stmt_planilha->bindValue(':cidade', $cidade);
    $stmt_planilha->bindValue(':assinatura_responsavel', $assinatura_responsavel);
    $stmt_planilha->bindValue(':setor', $setor, PDO::PARAM_INT);
        $stmt_planilha->execute();
        $id_planilha = $conexao->lastInsertId();

        // Salvar configurações de mapeamento na tabela config_planilha
        $mapeamento_string = '';
        foreach ($mapeamento as $coluna_banco => $letra_planilha) {
            $mapeamento_string .= "{$coluna_banco}={$letra_planilha};";
        }
        $mapeamento_string = rtrim($mapeamento_string, ';');

        $sql_config = "INSERT INTO config_planilha (id_planilha, pulo_linhas, mapeamento_colunas, comum, data_posicao, endereco, cnpj) 
                      VALUES (:id_planilha, :pulo_linhas, :mapeamento_colunas, :comum, :data_posicao, :endereco, :cnpj)";
        $stmt_config = $conexao->prepare($sql_config);
        $stmt_config->bindValue(':id_planilha', $id_planilha);
        $stmt_config->bindValue(':pulo_linhas', $linhas_pular);
        $stmt_config->bindValue(':mapeamento_colunas', $mapeamento_string);
        $stmt_config->bindValue(':comum', $localizacao_comum);
        $stmt_config->bindValue(':data_posicao', $localizacao_data_posicao);
        $stmt_config->bindValue(':endereco', $localizacao_endereco);
        $stmt_config->bindValue(':cnpj', $localizacao_cnpj);
        $stmt_config->execute();

        // Processar as linhas de dados do CSV
        $linhas = $aba_ativa->toArray();

        $registros_importados = 0;
        $registros_erros = 0;
        $linha_atual = 0;
        $erro_detalhado = '';

        // Função para converter letra da coluna para índice numérico
        function colunaParaIndice($coluna) {
            $coluna = strtoupper($coluna);
            $indice = 0;
            $tamanho = strlen($coluna);
            
            for ($i = 0; $i < $tamanho; $i++) {
                $indice = $indice * 26 + (ord($coluna[$i]) - ord('A') + 1);
            }
            
            return $indice - 1;
        }

        // Função para corrigir encoding dos textos
        function corrigirEncoding($texto) {
            if (empty($texto)) return $texto;
            
            $encoding = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            
            if ($encoding !== 'UTF-8') {
                $texto = mb_convert_encoding($texto, 'UTF-8', $encoding);
            }
            
            return $texto;
        }

        foreach ($linhas as $linha) {
            $linha_atual++;

            // Pular linhas iniciais
            if ($linha_atual <= $linhas_pular) {
                continue;
            }

            // Verificar se a linha está vazia
            if (empty(array_filter($linha, function($v) { return $v !== null && $v !== ''; }))) {
                continue;
            }

            try {
                // Obter valores baseado no mapeamento simplificado
                $indice_codigo = colunaParaIndice($mapeamento['codigo']);
                $codigo = isset($linha[$indice_codigo]) ? trim($linha[$indice_codigo]) : '';
                
                // Pular linha se não tiver código
                if (empty($codigo)) {
                    continue;
                }

                // Obter outros valores com correção de encoding
                $nome = isset($linha[colunaParaIndice($mapeamento['nome'])]) ? corrigirEncoding(trim($linha[colunaParaIndice($mapeamento['nome'])])) : '';
                $dependencia = isset($linha[colunaParaIndice($mapeamento['dependencia'])]) ? corrigirEncoding(trim($linha[colunaParaIndice($mapeamento['dependencia'])])) : '';

                // Inserir o produto (apenas campos necessários)
                $sql_produto = "INSERT INTO produtos 
                    (codigo, nome, dependencia, id_planilha) 
                VALUES 
                    (:codigo, :nome, :dependencia, :id_planilha)";

                $stmt_produto = $conexao->prepare($sql_produto);
                $stmt_produto->bindValue(':codigo', $codigo);
                $stmt_produto->bindValue(':nome', $nome);
                $stmt_produto->bindValue(':dependencia', $dependencia);
                $stmt_produto->bindValue(':id_planilha', $id_planilha);

                if ($stmt_produto->execute()) {
                    $registros_importados++;
                } else {
                    $registros_erros++;
                    $errorInfo = $stmt_produto->errorInfo();
                    $erro_detalhado = "Erro SQL: " . $errorInfo[2] . " na linha " . $linha_atual;
                    error_log($erro_detalhado);
                }

            } catch (Exception $e) {
                $registros_erros++;
                $erro_detalhado = "Erro na linha {$linha_atual}: " . $e->getMessage();
                error_log($erro_detalhado);
            }
        }

        if ($registros_importados === 0 && $registros_erros > 0) {
            throw new Exception("Nenhum registro foi importado. Erro: " . $erro_detalhado);
        }

        // Confirmar transação
        $conexao->commit();

        $mensagem = "Importação concluída com sucesso!<br>
                    Valor obtido da célula " . htmlspecialchars($localizacao_comum) . ": " . htmlspecialchars($valor_comum) . "<br>
                    Valor obtido da célula " . htmlspecialchars($localizacao_data_posicao) . ": " . htmlspecialchars($valor_data_posicao) . " (" . htmlspecialchars($data_mysql) . ")<br>
                    Valor obtido da célula " . htmlspecialchars($localizacao_endereco) . ": " . htmlspecialchars($valor_endereco) . "<br>
                    Valor obtido da célula " . htmlspecialchars($localizacao_cnpj) . ": " . htmlspecialchars($valor_cnpj) . " (" . htmlspecialchars($cnpj_somente_numeros) . ")<br>
                    Registros importados: {$registros_importados}<br>
                    Erros: {$registros_erros}";
        
        if (!empty($erro_detalhado)) {
            $mensagem .= "<br><br>Detalhes do último erro:<br>" . $erro_detalhado;
        }
        
        $tipo_mensagem = 'success';

    } catch (Exception $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $mensagem = "Erro na importação: " . $e->getMessage();
        $tipo_mensagem = 'error';
        
        error_log("ERRO IMPORTACAO: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
    }
}
?>