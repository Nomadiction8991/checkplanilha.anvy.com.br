<?php
require_once 'conexao.php';
require_once 'vendor/autoload.php';

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
    $descricao = trim($_POST['descricao'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $linhas_pular = (int)($_POST['linhas_pular'] ?? 25);
    
    // Mapeamento das colunas
    $mapeamento = [
        'codigo' => strtoupper($_POST['codigo'] ?? 'A'),
        'nome' => strtoupper($_POST['nome'] ?? 'D'),
        'fornecedor' => strtoupper($_POST['fornecedor'] ?? 'G'),
        'localidade' => strtoupper($_POST['localidade'] ?? 'K'),
        'conta' => strtoupper($_POST['conta'] ?? 'L'),
        'numero_documento' => strtoupper($_POST['numero_documento'] ?? 'N'),
        'dependencia' => strtoupper($_POST['dependencia'] ?? 'P'),
        'data_aquisicao' => strtoupper($_POST['data_aquisicao'] ?? 'T'),
        'valor_aquisicao' => strtoupper($_POST['valor_aquisicao'] ?? 'V'),
        'valor_depreciacao' => strtoupper($_POST['valor_depreciacao'] ?? 'W'),
        'valor_atual' => strtoupper($_POST['valor_atual'] ?? 'AB'),
        'status' => strtoupper($_POST['status_coluna'] ?? 'AF')
    ];

    try {
        // Validar campos obrigatórios
        if (empty($descricao)) {
            throw new Exception('A descrição é obrigatória.');
        }

        if (empty($status)) {
            throw new Exception('O status é obrigatório.');
        }

        // Validar se o status é um dos valores permitidos
        $status_permitidos = ['Pendente', 'Em Execução', 'Concluído'];
        if (!in_array($status, $status_permitidos)) {
            throw new Exception('Status inválido. Valores permitidos: Pendente, Em Execução, Concluído.');
        }

        // Iniciar transação para garantir consistência dos dados
        $conexao->beginTransaction();

        // Atualizar dados da planilha
        $sql_update_planilha = "UPDATE planilhas SET descricao = :descricao, status = :status, ativo = :ativo WHERE id = :id";
        $stmt_update_planilha = $conexao->prepare($sql_update_planilha);
        $stmt_update_planilha->bindValue(':descricao', $descricao);
        $stmt_update_planilha->bindValue(':status', $status);
        $stmt_update_planilha->bindValue(':ativo', $ativo);
        $stmt_update_planilha->bindValue(':id', $id_planilha);
        $stmt_update_planilha->execute();

        // Atualizar configurações de mapeamento
        $mapeamento_string = '';
        foreach ($mapeamento as $coluna_banco => $letra_planilha) {
            $mapeamento_string .= "{$coluna_banco}={$letra_planilha};";
        }
        $mapeamento_string = rtrim($mapeamento_string, ';');

        $sql_update_config = "UPDATE config_planilha SET pulo_linhas = :pulo_linhas, mapeamento_colunas = :mapeamento_colunas WHERE id_planilha = :id_planilha";
        $stmt_update_config = $conexao->prepare($sql_update_config);
        $stmt_update_config->bindValue(':pulo_linhas', $linhas_pular);
        $stmt_update_config->bindValue(':mapeamento_colunas', $mapeamento_string);
        $stmt_update_config->bindValue(':id_planilha', $id_planilha);
        $stmt_update_config->execute();

        // Processar arquivo se foi enviado
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
            $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
            $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));

            if ($extensao !== 'csv') {
                throw new Exception('Apenas arquivos CSV são permitidos.');
            }

            // Apagar todos os produtos existentes desta planilha
            $sql_delete_produtos = "DELETE FROM produtos WHERE id_planilha = :id_planilha";
            $stmt_delete_produtos = $conexao->prepare($sql_delete_produtos);
            $stmt_delete_produtos->bindValue(':id_planilha', $id_planilha);
            $stmt_delete_produtos->execute();

            // Processar o novo arquivo CSV
            $planilha_obj = IOFactory::load($arquivo_tmp);
            $aba_ativa = $planilha_obj->getActiveSheet();
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

            // Função para converter valores monetários
            function converterValor($valor) {
                if (empty(trim($valor))) return null;
                
                $valor = str_replace(['R$', ' ', '.'], '', trim($valor));
                $valor = str_replace(',', '.', $valor);
                $valor = preg_replace('/[^0-9.]/', '', $valor);
                
                return is_numeric($valor) ? (float)$valor : null;
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
                    // Obter valores baseado no mapeamento
                    $indice_codigo = colunaParaIndice($mapeamento['codigo']);
                    $codigo = isset($linha[$indice_codigo]) ? trim($linha[$indice_codigo]) : '';
                    
                    // Pular linha se não tiver código
                    if (empty($codigo)) {
                        continue;
                    }

                    // Obter outros valores
                    $nome = isset($linha[colunaParaIndice($mapeamento['nome'])]) ? trim($linha[colunaParaIndice($mapeamento['nome'])]) : '';
                    $fornecedor = isset($linha[colunaParaIndice($mapeamento['fornecedor'])]) ? trim($linha[colunaParaIndice($mapeamento['fornecedor'])]) : '';
                    $localidade = isset($linha[colunaParaIndice($mapeamento['localidade'])]) ? trim($linha[colunaParaIndice($mapeamento['localidade'])]) : '';
                    $conta = isset($linha[colunaParaIndice($mapeamento['conta'])]) ? trim($linha[colunaParaIndice($mapeamento['conta'])]) : '';
                    $numero_documento = isset($linha[colunaParaIndice($mapeamento['numero_documento'])]) ? trim($linha[colunaParaIndice($mapeamento['numero_documento'])]) : '';
                    $dependencia = isset($linha[colunaParaIndice($mapeamento['dependencia'])]) ? trim($linha[colunaParaIndice($mapeamento['dependencia'])]) : '';
                    
                    // Processar data
                    $data_aquisicao = null;
                    $data_raw = isset($linha[colunaParaIndice($mapeamento['data_aquisicao'])]) ? trim($linha[colunaParaIndice($mapeamento['data_aquisicao'])]) : '';
                    if (!empty($data_raw)) {
                        if (is_numeric($data_raw)) {
                            $data_aquisicao = Date::excelToDateTimeObject($data_raw)->format('Y-m-d');
                        } else {
                            $data_timestamp = strtotime(str_replace('/', '-', $data_raw));
                            if ($data_timestamp !== false) {
                                $data_aquisicao = date('Y-m-d', $data_timestamp);
                            }
                        }
                    }

                    $valor_aquisicao = converterValor(isset($linha[colunaParaIndice($mapeamento['valor_aquisicao'])]) ? $linha[colunaParaIndice($mapeamento['valor_aquisicao'])] : '');
                    $valor_depreciacao = converterValor(isset($linha[colunaParaIndice($mapeamento['valor_depreciacao'])]) ? $linha[colunaParaIndice($mapeamento['valor_depreciacao'])] : '');
                    $valor_atual = converterValor(isset($linha[colunaParaIndice($mapeamento['valor_atual'])]) ? $linha[colunaParaIndice($mapeamento['valor_atual'])] : '');
                    $status_produto = isset($linha[colunaParaIndice($mapeamento['status'])]) ? trim($linha[colunaParaIndice($mapeamento['status'])]) : '';

                    // Inserir o produto
                    $sql_produto = "INSERT INTO produtos 
                        (codigo, nome, fornecedor, localidade, conta, numero_documento, 
                         dependencia, data_aquisicao, valor_aquisicao, valor_depreciacao, 
                         valor_atual, status, id_planilha) 
                    VALUES 
                        (:codigo, :nome, :fornecedor, :localidade, :conta, :numero_documento,
                         :dependencia, :data_aquisicao, :valor_aquisicao, :valor_depreciacao,
                         :valor_atual, :status, :id_planilha)";

                    $stmt_produto = $conexao->prepare($sql_produto);
                    $stmt_produto->bindValue(':codigo', $codigo);
                    $stmt_produto->bindValue(':nome', $nome);
                    $stmt_produto->bindValue(':fornecedor', $fornecedor);
                    $stmt_produto->bindValue(':localidade', $localidade);
                    $stmt_produto->bindValue(':conta', $conta);
                    $stmt_produto->bindValue(':numero_documento', $numero_documento);
                    $stmt_produto->bindValue(':dependencia', $dependencia);
                    $stmt_produto->bindValue(':data_aquisicao', $data_aquisicao);
                    $stmt_produto->bindValue(':valor_aquisicao', $valor_aquisicao);
                    $stmt_produto->bindValue(':valor_depreciacao', $valor_depreciacao);
                    $stmt_produto->bindValue(':valor_atual', $valor_atual);
                    $stmt_produto->bindValue(':status', $status_produto);
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

            $mensagem_arquivo = " Arquivo reprocessado: {$registros_importados} registros importados, {$registros_erros} erros.";
        } else {
            $mensagem_arquivo = " Dados atualizados sem alteração do arquivo.";
        }

        // Confirmar transação
        $conexao->commit();

        $mensagem = "Planilha atualizada com sucesso!" . $mensagem_arquivo;
        $tipo_mensagem = 'success';

        // Recarregar dados atualizados
        $stmt_planilha->execute();
        $planilha = $stmt_planilha->fetch();
        
        $stmt_config->execute();
        $config = $stmt_config->fetch();
        
        // Atualizar mapeamento
        $mapeamento_array = [];
        $mapeamentos = explode(';', $config['mapeamento_colunas']);
        foreach ($mapeamentos as $mapeamento_item) {
            list($campo, $letra) = explode('=', $mapeamento_item);
            $mapeamento_array[$campo] = $letra;
        }

    } catch (Exception $e) {
        // Reverter transação em caso de erro
        if ($conexao->inTransaction()) {
            $conexao->rollBack();
        }
        $mensagem = "Erro na atualização: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO ATUALIZACAO: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Planilha</title>
    <style>
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { padding: 8px; width: 100%; max-width: 400px; }
        .mapeamento-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 15px 0; }
        .mapeamento-item { display: flex; align-items: center; gap: 10px; }
        .mapeamento-label { min-width: 150px; }
        .mapeamento-input { width: 60px; text-align: center; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        small { color: #666; font-style: italic; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
    </style>
</head>
<body>
    <h1>Editar Planilha</h1>

    <a href="index.php" style="display: inline-block; margin-bottom: 20px; padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">
        ← Voltar para Listagem
    </a>

    <?php if (!empty($mensagem)): ?>
        <div class="message <?php echo $tipo_mensagem; ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <!-- Campo Descrição -->
        <div class="form-group">
            <label for="descricao">Descrição da Planilha:</label>
            <input type="text" id="descricao" name="descricao" 
                   value="<?php echo htmlspecialchars($planilha['descricao'] ?? ''); ?>" 
                   required placeholder="Digite um nome para identificar esta planilha">
        </div>

        <!-- Campo Status -->
        <div class="form-group">
            <label for="status">Status da Planilha:</label>
            <select id="status" name="status" required>
                <option value="">Selecione um status</option>
                <option value="Pendente" <?php echo ($planilha['status'] ?? '') === 'Pendente' ? 'selected' : ''; ?>>Pendente</option>
                <option value="Em Execução" <?php echo ($planilha['status'] ?? '') === 'Em Execução' ? 'selected' : ''; ?>>Em Execução</option>
                <option value="Concluído" <?php echo ($planilha['status'] ?? '') === 'Concluído' ? 'selected' : ''; ?>>Concluído</option>
            </select>
        </div>

        <!-- Campo Ativo -->
        <div class="form-group checkbox-group">
            <label for="ativo">Planilha Ativa:</label>
            <input type="checkbox" id="ativo" name="ativo" value="1" 
                   <?php echo ($planilha['ativo'] ?? 0) == 1 ? 'checked' : ''; ?>>
            <small>Desmarque para inativar esta planilha</small>
        </div>

        <!-- Campo Arquivo (Opcional) -->
        <div class="form-group">
            <label for="arquivo">Novo Arquivo CSV (opcional):</label>
            <input type="file" id="arquivo" name="arquivo" accept=".csv">
            <small>Selecione um novo arquivo apenas se desejar atualizar os dados dos produtos</small>
        </div>

        <!-- Configurações de Mapeamento -->
        <h3>Configurações de Importação</h3>

        <!-- Linhas a pular -->
        <div class="form-group">
            <label for="linhas_pular">Linhas iniciais a pular:</label>
            <input type="number" id="linhas_pular" name="linhas_pular" 
                   value="<?php echo $config['pulo_linhas'] ?? 25; ?>" min="0" required>
            <small>Número de linhas do cabeçalho que devem ser ignoradas</small>
        </div>

        <!-- Mapeamento de Colunas -->
        <h3>Mapeamento de Colunas</h3>
        <p>Defina a letra da coluna para cada campo:</p>

        <div class="mapeamento-grid">
            <div class="mapeamento-item">
                <span class="mapeamento-label">Código:</span>
                <input type="text" class="mapeamento-input" name="codigo" 
                       value="<?php echo $mapeamento_array['codigo'] ?? 'A'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Nome:</span>
                <input type="text" class="mapeamento-input" name="nome" 
                       value="<?php echo $mapeamento_array['nome'] ?? 'D'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Fornecedor:</span>
                <input type="text" class="mapeamento-input" name="fornecedor" 
                       value="<?php echo $mapeamento_array['fornecedor'] ?? 'G'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Localidade:</span>
                <input type="text" class="mapeamento-input" name="localidade" 
                       value="<?php echo $mapeamento_array['localidade'] ?? 'K'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Conta:</span>
                <input type="text" class="mapeamento-input" name="conta" 
                       value="<?php echo $mapeamento_array['conta'] ?? 'L'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Número Documento:</span>
                <input type="text" class="mapeamento-input" name="numero_documento" 
                       value="<?php echo $mapeamento_array['numero_documento'] ?? 'N'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Dependência:</span>
                <input type="text" class="mapeamento-input" name="dependencia" 
                       value="<?php echo $mapeamento_array['dependencia'] ?? 'P'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Data Aquisição:</span>
                <input type="text" class="mapeamento-input" name="data_aquisicao" 
                       value="<?php echo $mapeamento_array['data_aquisicao'] ?? 'T'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Valor Aquisição:</span>
                <input type="text" class="mapeamento-input" name="valor_aquisicao" 
                       value="<?php echo $mapeamento_array['valor_aquisicao'] ?? 'V'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Valor Depreciação:</span>
                <input type="text" class="mapeamento-input" name="valor_depreciacao" 
                       value="<?php echo $mapeamento_array['valor_depreciacao'] ?? 'W'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Valor Atual:</span>
                <input type="text" class="mapeamento-input" name="valor_atual" 
                       value="<?php echo $mapeamento_array['valor_atual'] ?? 'AB'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Status (Coluna):</span>
                <input type="text" class="mapeamento-input" name="status_coluna" 
                       value="<?php echo $mapeamento_array['status'] ?? 'AF'; ?>" maxlength="2" required>
            </div>
        </div>

        <button type="submit">Atualizar Planilha</button>
        <a href="index.php" style="padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; display: inline-block; margin-left: 10px;">
            Cancelar
        </a>
    </form>
</body>
</html>
