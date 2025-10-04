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
    // Redirecionar mantendo os filtros
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
            'observacoes' => ''
        ];
    }
    
} catch (Exception $e) {
    $mensagem = "Erro ao carregar produto: " . $e->getMessage();
    $tipo_mensagem = 'error';
}

// Processar o formulário quando enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observacoes = trim($_POST['observacoes'] ?? '');
    $checado = isset($_POST['checado']) ? 1 : 0;
    
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
            $sql_update = "UPDATE produtos_check SET checado = :checado, observacoes = :observacoes WHERE produto_id = :produto_id";
            $stmt_update = $conexao->prepare($sql_update);
            $stmt_update->bindValue(':checado', $checado);
            $stmt_update->bindValue(':observacoes', $observacoes);
            $stmt_update->bindValue(':produto_id', $produto['id']);
            $stmt_update->execute();
        } else {
            // Inserir novo registro
            $sql_insert = "INSERT INTO produtos_check (produto_id, checado, observacoes) VALUES (:produto_id, :checado, :observacoes)";
            $stmt_insert = $conexao->prepare($sql_insert);
            $stmt_insert->bindValue(':produto_id', $produto['id']);
            $stmt_insert->bindValue(':checado', $checado);
            $stmt_insert->bindValue(':observacoes', $observacoes);
            $stmt_insert->execute();
        }
        
        // Atualizar dados locais
        $check['checado'] = $checado;
        $check['observacoes'] = $observacoes;
        
        $mensagem = "Alterações salvas com sucesso!";
        $tipo_mensagem = 'success';
        
    } catch (Exception $e) {
        $mensagem = "Erro ao salvar alterações: " . $e->getMessage();
        $tipo_mensagem = 'error';
        error_log("ERRO SALVAR PRODUTO: " . $e->getMessage());
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
    <title>Editar Produto - <?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { padding: 8px; width: 100%; max-width: 500px; box-sizing: border-box; }
        .readonly-field { background-color: #f8f9fa; border: 1px solid #ced4da; color: #6c757d; padding: 8px; border-radius: 4px; }
        
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
        
        .btn { padding: 10px 20px; text-decoration: none; border-radius: 4px; border: none; cursor: pointer; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        .product-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .product-info h3 { margin-top: 0; }
        
        .filter-info { background: #e9ecef; padding: 10px; border-radius: 5px; margin-bottom: 15px; font-size: 14px; }
        .filter-info strong { color: #495057; }
    </style>
</head>
<body>
    <h1>Editar Produto</h1>

    <!-- Informações dos filtros ativos -->
    <div class="filter-info">
        <strong>Filtros ativos:</strong>
        <?php if ($filtro_codigo): ?>Código: <?php echo htmlspecialchars($filtro_codigo); ?> | <?php endif; ?>
        <?php if ($filtro_nome): ?>Nome: <?php echo htmlspecialchars($filtro_nome); ?> | <?php endif; ?>
        <?php if ($filtro_dependencia): ?>Dependência: <?php echo htmlspecialchars($filtro_dependencia); ?> | <?php endif; ?>
        Página: <?php echo $pagina; ?>
    </div>

    <a href="<?php echo getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo); ?>" class="btn btn-secondary">
        ← Voltar para Planilha
    </a>

    <?php if (!empty($mensagem)): ?>
        <div class="message <?php echo $tipo_mensagem; ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <!-- Informações do Produto (somente leitura) -->
    <div class="product-info">
        <h3>Informações do Produto</h3>
        
        <div class="form-group">
            <label for="codigo">Código:</label>
            <div class="readonly-field"><?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></div>
        </div>

        <div class="form-group">
            <label for="nome">Nome:</label>
            <div class="readonly-field"><?php echo htmlspecialchars($produto['nome'] ?? ''); ?></div>
        </div>

        <div class="form-group">
            <label for="dependencia">Dependência:</label>
            <div class="readonly-field"><?php echo htmlspecialchars($produto['dependencia'] ?? ''); ?></div>
        </div>

        <div class="form-group">
            <label for="status">Status:</label>
            <div class="readonly-field"><?php echo htmlspecialchars($produto['status'] ?? ''); ?></div>
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
            <label for="observacoes">Observações:</label>
            <textarea id="observacoes" name="observacoes" class="form-control" rows="4" 
                      placeholder="Digite observações sobre este produto..."><?php echo htmlspecialchars($check['observacoes'] ?? ''); ?></textarea>
        </div>

        <!-- Checkbox Marcar como Checado -->
        <div class="form-group checkbox-group">
            <input type="checkbox" id="checado" name="checado" value="1" 
                   <?php echo ($check['checado'] ?? 0) == 1 ? 'checked' : ''; ?>>
            <label for="checado">Marcar como checado</label>
        </div>

        <!-- Botões -->
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="<?php echo getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo); ?>" class="btn btn-secondary">Cancelar</a>
    </form>

    <!-- Informações adicionais -->
    <div style="margin-top: 30px; font-size: 14px; color: #666;">
        <p><strong>Informações:</strong></p>
        <ul>
            <li>Os campos Código, Nome, Dependência e Status são apenas para visualização</li>
            <li>O campo Observações pode ser editado livremente</li>
            <li>Marque "Marcar como checado" quando o produto for verificado fisicamente</li>
            <li>As alterações serão salvas na tabela de controle de produtos</li>
            <li>Ao salvar ou cancelar, você retornará para a página <?php echo $pagina; ?> com os filtros aplicados</li>
        </ul>
    </div>
</body>
</html>