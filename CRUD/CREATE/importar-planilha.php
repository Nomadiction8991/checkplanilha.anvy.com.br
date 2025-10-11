<?php
require_once '../CRUD/conexao.php';
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao'] ?? '');
    $linhas_pular = (int)($_POST['linhas_pular'] ?? 25);
    $comum = trim($_POST['comum'] ?? 'D16'); // Novo campo
    
    // Mapeamento simplificado - removendo campos desnecessários
    $mapeamento = [
        'codigo' => strtoupper($_POST['codigo'] ?? 'A'),
        'nome' => strtoupper($_POST['nome'] ?? 'D'),
        'dependencia' => strtoupper($_POST['dependencia'] ?? 'P'),
    ];

    $mensagem = '';
    $tipo_mensagem = '';

    try {
        // Validar campos obrigatórios
        if (empty($descricao)) {
            throw new Exception('A descrição é obrigatória.');
        }

        if (empty($comum)) {
            throw new Exception('O campo comum é obrigatório.');
        }

        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Selecione um arquivo CSV válido.');
        }

        $arquivo_tmp = $_FILES['arquivo']['tmp_name'];
        $extensao = strtolower(pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION));

        if ($extensao !== 'csv') {
            throw new Exception('Apenas arquivos CSV são permitidos.');
        }

        // Iniciar transação
        $conexao->beginTransaction();

        // Inserir a planilha na tabela planilhas
        $sql_planilha = "INSERT INTO planilhas (descricao, comum) VALUES (:descricao, :comum)";
        $stmt_planilha = $conexao->prepare($sql_planilha);
        $stmt_planilha->bindValue(':descricao', $descricao);
        $stmt_planilha->bindValue(':comum', $comum);
        $stmt_planilha->execute();
        $id_planilha = $conexao->lastInsertId();

        // Salvar configurações de mapeamento na tabela config_planilha
        $mapeamento_string = '';
        foreach ($mapeamento as $coluna_banco => $letra_planilha) {
            $mapeamento_string .= "{$coluna_banco}={$letra_planilha};";
        }
        $mapeamento_string = rtrim($mapeamento_string, ';');

        $sql_config = "INSERT INTO config_planilha (id_planilha, pulo_linhas, mapeamento_colunas, comum) 
                      VALUES (:id_planilha, :pulo_linhas, :mapeamento_colunas, :comum)";
        $stmt_config = $conexao->prepare($sql_config);
        $stmt_config->bindValue(':id_planilha', $id_planilha);
        $stmt_config->bindValue(':pulo_linhas', $linhas_pular);
        $stmt_config->bindValue(':mapeamento_colunas', $mapeamento_string);
        $stmt_config->bindValue(':comum', $comum);
        $stmt_config->execute();

        // Processar o arquivo CSV
        $planilha = IOFactory::load($arquivo_tmp);
        $aba_ativa = $planilha->getActiveSheet();
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
                    Planilha: {$descricao}<br>
                    Comum: {$comum}<br>
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

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Planilha</title>
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
        .debug-info { background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Importar Planilha</h1>

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
                   value="<?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?>" 
                   required placeholder="Digite um nome para identificar esta planilha">
        </div>

        <!-- Campo Comum -->
        <div class="form-group">
            <label for="comum">Localização Comum:</label>
            <input type="text" id="comum" name="comum" 
                   value="<?php echo htmlspecialchars($_POST['comum'] ?? 'D16'); ?>" 
                   required placeholder="Ex: D16">
            <small>Localização padrão que será salva na planilha</small>
        </div>

        <!-- Campo Arquivo -->
        <div class="form-group">
            <label for="arquivo">Arquivo CSV:</label>
            <input type="file" id="arquivo" name="arquivo" accept=".csv" required>
        </div>

        <!-- Configurações de Mapeamento -->
        <h3>Configurações de Importação</h3>

        <!-- Linhas a pular -->
        <div class="form-group">
            <label for="linhas_pular">Linhas iniciais a pular:</label>
            <input type="number" id="linhas_pular" name="linhas_pular" 
                   value="<?php echo $_POST['linhas_pular'] ?? 25; ?>" min="0" required>
            <small>Número de linhas do cabeçalho que devem ser ignoradas</small>
        </div>

        <!-- Mapeamento de Colunas Simplificado -->
        <h3>Mapeamento de Colunas</h3>
        <p>Defina a letra da coluna para cada campo:</p>

        <div class="mapeamento-grid">
            <div class="mapeamento-item">
                <span class="mapeamento-label">Código:</span>
                <input type="text" class="mapeamento-input" name="codigo" 
                       value="<?php echo $_POST['codigo'] ?? 'A'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Nome:</span>
                <input type="text" class="mapeamento-input" name="nome" 
                       value="<?php echo $_POST['nome'] ?? 'D'; ?>" maxlength="2" required>
            </div>

            <div class="mapeamento-item">
                <span class="mapeamento-label">Dependência:</span>
                <input type="text" class="mapeamento-input" name="dependencia" 
                       value="<?php echo $_POST['dependencia'] ?? 'P'; ?>" maxlength="2" required>
            </div>
        </div>

        <button type="submit">Importar Planilha</button>
    </form>
</body>
</html>