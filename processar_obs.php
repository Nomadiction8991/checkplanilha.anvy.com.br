<?php
require_once 'conexao.php';

$codigo = $_GET['codigo'] ?? null;
$id_planilha = $_GET['id_planilha'] ?? null;

// Receber filtros
$pagina = $_GET['pagina'] ?? 1;
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['filtro_codigo'] ?? '';

if (!$codigo || !$id_planilha) {
    $query_string = http_build_query([
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo,
        'erro' => 'Produto não encontrado'
    ]);
    header('Location: visualizar_planilha.php?' . $query_string);
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// Buscar dados do produto
try {
    $sql_produto = "SELECT * FROM produtos WHERE codigo = :codigo AND id_planilha = :id_planilha";
    $stmt_produto = $conexao->prepare($sql_produto);
    $stmt_produto->bindValue(':codigo', $codigo);
    $stmt_produto->bindValue(':id_planilha', $id_planilha);
    $stmt_produto->execute();
    $produto = $stmt_produto->fetch();
    
    if (!$produto) {
        throw new Exception('Produto não encontrado.');
    }
    
    // Buscar dados do check (se existir)
    $sql_check = "SELECT * FROM produtos_check WHERE produto_id = :produto_id";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bindValue(':produto_id', $produto['id']);
    $stmt_check->execute();
    $check = $stmt_check->fetch();

    // Se não existir registro, criar array vazio
    if (!$check) {
        $check = [
            'checado' => 0,
            'observacoes' => '',
            'dr' => 0,
            'imprimir' => 0
        ];
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observacoes = trim($_POST['observacoes'] ?? '');
    
    // Receber filtros do POST também
    $pagina = $_POST['pagina'] ?? 1;
    $filtro_nome = $_POST['nome'] ?? '';
    $filtro_dependencia = $_POST['dependencia'] ?? '';
    $filtro_codigo = $_POST['filtro_codigo'] ?? '';
    
    try {
        // Verificar se já existe registro na tabela produtos_check
        $sql_verificar = "SELECT COUNT(*) as total FROM produtos_check WHERE produto_id = :produto_id";
        $stmt_verificar = $conexao->prepare($sql_verificar);
        $stmt_verificar->bindValue(':produto_id', $produto['id']);
        $stmt_verificar->execute();
        $existe_registro = $stmt_verificar->fetch()['total'] > 0;

        if ($existe_registro) {
            // Atualizar registro existente
            $sql_update = "UPDATE produtos_check SET observacoes = :observacoes WHERE produto_id = :produto_id";
            $stmt_update = $conexao->prepare($sql_update);
            $stmt_update->bindValue(':observacoes', $observacoes);
            $stmt_update->bindValue(':produto_id', $produto['id']);
            $stmt_update->execute();
        } else {
            // Inserir novo registro
            $sql_insert = "INSERT INTO produtos_check (produto_id, observacoes) VALUES (:produto_id, :observacoes)";
            $stmt_insert = $conexao->prepare($sql_insert);
            $stmt_insert->bindValue(':produto_id', $produto['id']);
            $stmt_insert->bindValue(':observacoes', $observacoes);
            $stmt_insert->execute();
        }
        
        // Atualizar dados locais
        $check['observacoes'] = $observacoes;
        
        $mensagem = "Observações salvas com sucesso!";
        $tipo_mensagem = 'success';
        
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar observações: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO SALVAR OBSERVAÇÕES: " . $e->getMessage());
    }
}

// Função para gerar URL de retorno com filtros
function getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo) {
    $params = [
        'id' => $id_planilha,
        'pagina' => $pagina,
        'nome' => $filtro_nome,
        'dependencia' => $filtro_dependencia,
        'codigo' => $filtro_codigo
    ];
    return 'visualizar_planilha.php?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Observações - <?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
        }

        header {
            background: #007bff;
            padding: 5px 10px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 50px;
        }

        .header-title {
            width: 50%;
            font-size: 16px;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .header-actions {
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
        }

        .header-btn {
            background: none;
            border: none;
            color: #fff;
            cursor: pointer;
            padding: 8px;
            border-radius: 4px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.2s;
            text-decoration: none;
        }

        .header-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .container {
            padding: 15px;
        }

        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .product-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .product-field {
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .product-field:last-child {
            border-bottom: none;
        }

        .field-label {
            font-weight: bold;
            color: #495057;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .field-value {
            color: #212529;
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .filter-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .filter-info strong {
            color: #495057;
        }
    </style>
</head>
<body>
    <header>
        <a href="<?php echo getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo); ?>" class="header-btn" title="Fechar">❌</a>
        <h1 class="header-title">Editar Observações - <?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></h1>
        <div class="header-actions"></div>
    </header>

    <div class="container">
        <!-- Informações dos filtros ativos -->
        <div class="filter-info">
            <strong>Filtros ativos:</strong>
            <?php if ($filtro_codigo): ?>Código: <?php echo htmlspecialchars($filtro_codigo); ?> | <?php endif; ?>
            <?php if ($filtro_nome): ?>Nome: <?php echo htmlspecialchars($filtro_nome); ?> | <?php endif; ?>
            <?php if ($filtro_dependencia): ?>Dependência: <?php echo htmlspecialchars($filtro_dependencia); ?> | <?php endif; ?>
            Página: <?php echo $pagina; ?>
        </div>

        <?php if (!empty($mensagem)): ?>
            <div class="message <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- Informações do Produto -->
        <div class="product-card">
            <h3 style="margin-top: 0; color: #007bff;">Informações do Produto</h3>
            
            <div class="product-field">
                <div class="field-label">Código:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></div>
            </div>

            <div class="product-field">
                <div class="field-label">Nome:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['nome'] ?? ''); ?></div>
            </div>

            <div class="product-field">
                <div class="field-label">Dependência:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['dependencia'] ?? ''); ?></div>
            </div>
        </div>

        <!-- Formulário de Edição -->
        <form method="POST" action="">
            <!-- Campos hidden para preservar filtros -->
            <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
            <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
            <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
            <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">

            <!-- Campo Observações -->
            <div class="form-group">
                <label for="observacoes" class="form-label">Observações:</label>
                <textarea id="observacoes" name="observacoes" class="form-control" rows="6" 
                          placeholder="Digite observações sobre este produto..."><?php echo htmlspecialchars($check['observacoes'] ?? ''); ?></textarea>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary">Salvar Observações</button>
        </form>
    </div>
</body>
</html>