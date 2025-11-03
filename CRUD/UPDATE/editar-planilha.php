<?php
require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$id_planilha = $_GET['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$id_planilha) {
    header('Location: ../index.php');
    exit;
}

try {
    $sql_planilha = "SELECT * FROM planilhas WHERE id = :id";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':id', $id_planilha);
    $stmt_planilha->execute();
    $planilha = $stmt_planilha->fetch();
    
    if (!$planilha) {
        throw new Exception('Planilha não encontrada.');
    }
    
    $sql_config = "SELECT * FROM config_planilha WHERE id_planilha = :id_planilha";
    $stmt_config = $conexao->prepare($sql_config);
    $stmt_config->bindValue(':id_planilha', $id_planilha);
    $stmt_config->execute();
    $config = $stmt_config->fetch();
    
    if (!$config) {
        throw new Exception('Configurações da planilha não encontradas.');
    }
    
    // Converter mapeamento de string para array
    $mapeamento_array = [];
    $mapeamentos = explode(';', $config['mapeamento_colunas']);
    foreach ($mapeamentos as $mapeamento) {
        list($campo, $letra) = explode('=', $mapeamento);
        $mapeamento_array[$campo] = $letra;
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar planilha: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $linhas_pular = (int)($_POST['linhas_pular'] ?? 25);
    $localizacao_comum = trim($_POST['localizacao_comum'] ?? 'D16');
    $localizacao_data_posicao = trim($_POST['localizacao_data_posicao'] ?? 'D13');
    $localizacao_endereco = trim($_POST['localizacao_endereco'] ?? 'A4');
    $localizacao_cnpj = trim($_POST['localizacao_cnpj'] ?? 'U8');
    // administracao (estado) e cidade
    $administracao = trim($_POST['administracao'] ?? null);
    $cidade = trim($_POST['cidade'] ?? null);
    // Setor (opcional, numérico)
    $setor = !empty($_POST['setor']) ? (int)$_POST['setor'] : null;
    
    // Mapeamento simplificado
    $mapeamento = [
        'codigo' => strtoupper($_POST['codigo'] ?? 'A'),
        'nome' => strtoupper($_POST['nome'] ?? 'D'),
        'dependencia' => strtoupper($_POST['dependencia'] ?? 'P'),
    ];

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

        // Iniciar transação
        $conexao->beginTransaction();

    // Se um novo arquivo foi enviado, processar para obter os novos valores
    $novo_valor_comum = $planilha['comum']; // Manter o valor atual por padrão
    $novo_valor_data_posicao = $planilha['data_posicao']; // Manter o valor atual por padrão
    $novo_valor_endereco = $planilha['endereco']; // Manter o valor atual por padrão
    $novo_valor_cnpj = $planilha['cnpj']; // Manter o valor atual por padrão
    $novo_administracao = $planilha['administracao'] ?? null;
    $novo_cidade = $planilha['cidade'] ?? null;
        
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
            $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));

            if ($extensao !== 'csv') {
                throw new Exception('Apenas arquivos CSV são permitidos.');
            }

            // Processar o arquivo CSV para obter os valores das células
            $planilha_obj = IOFactory::load($arquivo_tmp);
            $aba_ativa = $planilha_obj->getActiveSheet();
            
            // Obter o valor da célula comum
            $novo_valor_comum = $aba_ativa->getCell($localizacao_comum)->getCalculatedValue();
            
            if (empty($novo_valor_comum)) {
                throw new Exception('A célula ' . $localizacao_comum . ' está vazia no arquivo CSV.');
            }

            // Obter o valor da célula data_posicao
            $valor_data_posicao = $aba_ativa->getCell($localizacao_data_posicao)->getCalculatedValue();
            
            if (empty($valor_data_posicao)) {
                throw new Exception('A célula ' . $localizacao_data_posicao . ' está vazia no arquivo CSV.');
            }

            // Obter o valor da célula endereco
            $novo_valor_endereco = $aba_ativa->getCell($localizacao_endereco)->getCalculatedValue();
            
            if (empty($novo_valor_endereco)) {
                throw new Exception('A célula ' . $localizacao_endereco . ' está vazia no arquivo CSV.');
            }

            // Obter o valor da célula CNPJ e extrair apenas números
            $valor_cnpj = $aba_ativa->getCell($localizacao_cnpj)->getCalculatedValue();
            $cnpj_somente_numeros = preg_replace('/[^0-9]/', '', $valor_cnpj);
            $novo_valor_cnpj = $cnpj_somente_numeros;

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
            $novo_valor_data_posicao = $data_mysql;

            // Apagar todos os produtos existentes desta planilha
            $sql_delete_produtos = "DELETE FROM produtos WHERE id_planilha = :id_planilha";
            $stmt_delete_produtos = $conexao->prepare($sql_delete_produtos);
            $stmt_delete_produtos->bindValue(':id_planilha', $id_planilha);
            $stmt_delete_produtos->execute();

            // Processar as linhas de dados do novo arquivo CSV
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
        }

        // Validações de campos obrigatórios enviados pelo form
        if (isset($_POST['administracao']) && trim($_POST['administracao']) === '') {
            throw new Exception('O campo Administração é obrigatório.');
        }
        if (isset($_POST['cidade']) && trim($_POST['cidade']) === '') {
            throw new Exception('O campo Cidade é obrigatório.');
        }

        // Se o usuário submeteu administracao/cidade via POST, sobrescrever as variáveis de update
        if (!empty($administracao) || $administracao === "") {
            $novo_administracao = $administracao;
        }
        if (!empty($cidade) || $cidade === "") {
            $novo_cidade = $cidade;
        }
        
        // Atualizar setor se foi fornecido
        $novo_setor = $planilha['setor'] ?? null;
        if (isset($_POST['setor'])) {
            $novo_setor = $setor;
        }

        // Atualizar dados da planilha com os novos valores (se aplicável)
    $sql_update_planilha = "UPDATE planilhas SET ativo = :ativo, comum = :comum, data_posicao = :data_posicao, endereco = :endereco, cnpj = :cnpj, administracao = :administracao, cidade = :cidade, setor = :setor WHERE id = :id";
        $stmt_update_planilha = $conexao->prepare($sql_update_planilha);
        $stmt_update_planilha->bindValue(':ativo', $ativo);
        $stmt_update_planilha->bindValue(':comum', $novo_valor_comum);
        $stmt_update_planilha->bindValue(':data_posicao', $novo_valor_data_posicao);
        $stmt_update_planilha->bindValue(':endereco', $novo_valor_endereco);
        $stmt_update_planilha->bindValue(':cnpj', $novo_valor_cnpj);
    $stmt_update_planilha->bindValue(':administracao', $novo_administracao);
    $stmt_update_planilha->bindValue(':cidade', $novo_cidade);
    $stmt_update_planilha->bindValue(':setor', $novo_setor, PDO::PARAM_INT);
        $stmt_update_planilha->bindValue(':id', $id_planilha);
        $stmt_update_planilha->execute();

        // Atualizar configurações de mapeamento
        $mapeamento_string = '';
        foreach ($mapeamento as $coluna_banco => $letra_planilha) {
            $mapeamento_string .= "{$coluna_banco}={$letra_planilha};";
        }
        $mapeamento_string = rtrim($mapeamento_string, ';');

        $sql_update_config = "UPDATE config_planilha SET pulo_linhas = :pulo_linhas, mapeamento_colunas = :mapeamento_colunas, comum = :comum, data_posicao = :data_posicao, endereco = :endereco, cnpj = :cnpj WHERE id_planilha = :id_planilha";
        $stmt_update_config = $conexao->prepare($sql_update_config);
        $stmt_update_config->bindValue(':pulo_linhas', $linhas_pular);
        $stmt_update_config->bindValue(':mapeamento_colunas', $mapeamento_string);
        $stmt_update_config->bindValue(':comum', $localizacao_comum);
        $stmt_update_config->bindValue(':data_posicao', $localizacao_data_posicao);
        $stmt_update_config->bindValue(':endereco', $localizacao_endereco);
        $stmt_update_config->bindValue(':cnpj', $localizacao_cnpj);
        $stmt_update_config->bindValue(':id_planilha', $id_planilha);
        $stmt_update_config->execute();

        // Confirmar transação
        $conexao->commit();

        $mensagem = "Planilha atualizada com sucesso!";
        
        if (isset($registros_importados)) {
            $mensagem .= "<br>Valor obtido da célula " . htmlspecialchars($localizacao_comum) . ": " . htmlspecialchars($novo_valor_comum);
            $mensagem .= "<br>Valor obtido da célula " . htmlspecialchars($localizacao_data_posicao) . ": " . htmlspecialchars($novo_valor_data_posicao);
            $mensagem .= "<br>Valor obtido da célula " . htmlspecialchars($localizacao_endereco) . ": " . htmlspecialchars($novo_valor_endereco);
            $mensagem .= "<br>Valor obtido da célula " . htmlspecialchars($localizacao_cnpj) . ": " . htmlspecialchars($novo_valor_cnpj);
            $mensagem .= "<br>Registros importados: {$registros_importados}<br>Erros: {$registros_erros}";
        }
        
        $tipo_mensagem = 'success';

        // Recarregar dados atualizados
        $stmt_planilha->execute();
        $planilha = $stmt_planilha->fetch();
        
        $stmt_config->execute();
        $config = $stmt_config->fetch();
        
        $mapeamento_array = [];
        $mapeamentos = explode(';', $config['mapeamento_colunas']);
        foreach ($mapeamentos as $mapeamento) {
            list($campo, $letra) = explode('=', $mapeamento);
            $mapeamento_array[$campo] = $letra;
        }

    } catch (Exception $e) {
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $mensagem = "Erro na atualização: " . $e->getMessage();
        $tipo_mensagem = 'error';
        
        error_log("ERRO ATUALIZACAO: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
    }
}
?>