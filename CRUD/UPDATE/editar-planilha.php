<?php
require_once '../CRUD/conexao.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$id_planilha = $_GET['id'] ?? null;
$mensagem = '';
$tipo_mensagem = '';

if (!$id_planilha) {
    header('Location: index.php');
    exit;
}

// Buscar dados da planilha
try {
    $sql_planilha = "SELECT * FROM planilhas WHERE id = :id";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':id', $id_planilha);
    $stmt_planilha->execute();
    $planilha = $stmt_planilha->fetch();
    
    if (!$planilha) {
        throw new Exception('Planilha não encontrada.');
    }
    
    // Buscar configurações de mapeamento
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
    $localizacao_comum = trim($_POST['localizacao_comum'] ?? 'D16'); // Nome corrigido
    
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

        // Iniciar transação
        $conexao->beginTransaction();

        // Se um novo arquivo foi enviado, processar para obter o novo valor comum
        $novo_valor_comum = $planilha['comum']; // Manter o valor atual por padrão
        
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
            $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));

            if ($extensao !== 'csv') {
                throw new Exception('Apenas arquivos CSV são permitidos.');
            }

            // Processar o arquivo CSV para obter o valor da célula comum
            $planilha_obj = IOFactory::load($arquivo_tmp);
            $aba_ativa = $planilha_obj->getActiveSheet();
            
            // Função para converter referência de célula (ex: D16) para coordenadas
            function referenciaParaCoordenadas($referencia) {
                preg_match('/([A-Z]+)(\d+)/', $referencia, $matches);
                if (count($matches) !== 3) {
                    throw new Exception('Formato de referência de célula inválido: ' . $referencia);
                }
                
                $coluna = $matches[1];
                $linha = (int)$matches[2];
                
                // Converter coluna para índice (A=0, B=1, etc.)
                $indice_coluna = 0;
                $tamanho = strlen($coluna);
                for ($i = 0; $i < $tamanho; $i++) {
                    $indice_coluna = $indice_coluna * 26 + (ord($coluna[$i]) - ord('A') + 1);
                }
                $indice_coluna--; // Ajustar para base 0
                
                return ['coluna' => $indice_coluna, 'linha' => $linha - 1]; // Ajustar linha para base 0
            }

            // Obter o valor da célula comum
            $coordenadas_comum = referenciaParaCoordenadas($localizacao_comum);
            $novo_valor_comum = $aba_ativa->getCellByColumnAndRow($coordenadas_comum['coluna'] + 1, $coordenadas_comum['linha'] + 1)->getValue();
            
            if (empty($novo_valor_comum)) {
                throw new Exception('A célula ' . $localizacao_comum . ' está vazia no arquivo CSV.');
            }

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

        // Atualizar dados da planilha com o novo valor comum (se aplicável)
        $sql_update_planilha = "UPDATE planilhas SET ativo = :ativo, comum = :comum WHERE id = :id";
        $stmt_update_planilha = $conexao->prepare($sql_update_planilha);
        $stmt_update_planilha->bindValue(':ativo', $ativo);
        $stmt_update_planilha->bindValue(':comum', $novo_valor_comum);
        $stmt_update_planilha->bindValue(':id', $id_planilha);
        $stmt_update_planilha->execute();

        // Atualizar configurações de mapeamento
        $mapeamento_string = '';
        foreach ($mapeamento as $coluna_banco => $letra_planilha) {
            $mapeamento_string .= "{$coluna_banco}={$letra_planilha};";
        }
        $mapeamento_string = rtrim($mapeamento_string, ';');

        $sql_update_config = "UPDATE config_planilha SET pulo_linhas = :pulo_linhas, mapeamento_colunas = :mapeamento_colunas, localizacao_comum = :localizacao_comum WHERE id_planilha = :id_planilha";
        $stmt_update_config = $conexao->prepare($sql_update_config);
        $stmt_update_config->bindValue(':pulo_linhas', $linhas_pular);
        $stmt_update_config->bindValue(':mapeamento_colunas', $mapeamento_string);
        $stmt_update_config->bindValue(':localizacao_comum', $localizacao_comum);
        $stmt_update_config->bindValue(':id_planilha', $id_planilha);
        $stmt_update_config->execute();

        // Confirmar transação
        $conexao->commit();

        $mensagem = "Planilha atualizada com sucesso!";
        
        if (isset($registros_importados)) {
            $mensagem .= "<br>Valor obtido da célula " . htmlspecialchars($localizacao_comum) . ": " . htmlspecialchars($novo_valor_comum);
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