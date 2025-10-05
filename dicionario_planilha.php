<?php
require_once 'conexao.php';

// Processar atualização de palavras
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_palavras'])) {
    try {
        $conexao->beginTransaction();
        
        foreach ($_POST['corrigido'] as $id => $corrigido) {
            if (!empty(trim($corrigido))) {
                $sql_update = "UPDATE dicionario SET corrigido = :corrigido WHERE id = :id";
                $stmt_update = $conexao->prepare($sql_update);
                $stmt_update->bindValue(':corrigido', trim($corrigido));
                $stmt_update->bindValue(':id', $id);
                $stmt_update->execute();
            }
        }
        
        $conexao->commit();
        $mensagem = "Palavras atualizadas com sucesso!";
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $conexao->rollBack();
        $mensagem = "Erro ao atualizar palavras: " . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}

// Processar atualização em massa nos produtos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar_correcoes'])) {
    try {
        $conexao->beginTransaction();
        
        // Buscar todas as correções
        $sql_correcoes = "SELECT incorreto, corrigido FROM dicionario WHERE corrigido IS NOT NULL";
        $stmt_correcoes = $conexao->query($sql_correcoes);
        $correcoes = $stmt_correcoes->fetchAll();
        
        $colunas_texto = ['nome', 'fornecedor', 'localidade', 'conta', 'numero_documento', 'dependencia', 'observacoes'];
        
        foreach ($correcoes as $correcao) {
            foreach ($colunas_texto as $coluna) {
                $sql_update_produto = "UPDATE produtos SET $coluna = REPLACE($coluna, :incorreto, :corrigido) WHERE $coluna LIKE CONCAT('%', :incorreto, '%')";
                $stmt_update_produto = $conexao->prepare($sql_update_produto);
                $stmt_update_produto->bindValue(':incorreto', $correcao['incorreto']);
                $stmt_update_produto->bindValue(':corrigido', $correcao['corrigido']);
                $stmt_update_produto->execute();
            }
        }
        
        $conexao->commit();
        $mensagem = "Correções aplicadas em todos os produtos com sucesso!";
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        $conexao->rollBack();
        $mensagem = "Erro ao aplicar correções: " . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}

// Filtros
$filtro_status = $_GET['status'] ?? 'incorretas';

// Buscar palavras do dicionário
if ($filtro_status === 'incorretas') {
    $sql_dicionario = "SELECT * FROM dicionario WHERE corrigido IS NULL ORDER BY incorreto";
} else {
    $sql_dicionario = "SELECT * FROM dicionario WHERE corrigido IS NOT NULL ORDER BY incorreto";
}

$stmt_dicionario = $conexao->query($sql_dicionario);
$palavras = $stmt_dicionario->fetchAll();

// Contar totais
$sql_total_incorretas = "SELECT COUNT(*) as total FROM dicionario WHERE corrigido IS NULL";
$stmt_total_incorretas = $conexao->query($sql_total_incorretas);
$total_incorretas = $stmt_total_incorretas->fetch()['total'];

$sql_total_corrigidas = "SELECT COUNT(*) as total FROM dicionario WHERE corrigido IS NOT NULL";
$stmt_total_corrigidas = $conexao->query($sql_total_corrigidas);
$total_corrigidas = $stmt_total_corrigidas->fetch()['total'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dicionário de Palavras</title>
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

    .message {
        padding: 10px;
        margin: 10px;
        border-radius: 4px;
        text-align: center;
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

    .filtros {
        padding: 15px;
        text-align: center;
        background: #f8f9fa;
        margin: 10px;
        border-radius: 5px;
    }

    .filtros select, .filtros button {
        padding: 8px 15px;
        margin: 0 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .filtros button {
        background: #007bff;
        color: white;
        border: none;
        cursor: pointer;
    }

    .filtros button:hover {
        background: #0056b3;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        padding: 12px 8px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background: #007bff;
        color: #fff;
    }

    input[type="text"] {
        padding: 8px;
        width: 100%;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .btn-action {
        background: #f8f9fa;
        border: 2px solid #6c757d;
        cursor: pointer;
        padding: 8px 15px;
        font-size: 16px;
        border-radius: 4px;
        transition: all 0.2s;
        text-decoration: none;
        color: #333;
        display: inline-block;
        margin: 5px;
    }

    .btn-action:hover {
        border-color: #007bff;
    }

    .form-actions {
        text-align: center;
        margin: 20px 0;
    }

    .stats {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin: 15px 0;
    }

    .stat-item {
        background: #f8f9fa;
        padding: 10px 20px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
        text-align: center;
    }

    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #007bff;
    }
    </style>
</head>
<body>
    <header>
        <a href="index.php" class="header-btn" title="Fechar">❌</a>
        <h1 class="header-title">Dicionário de Palavras</h1>
        <div class="header-actions">
            <form method="POST" style="display: inline;">
                <button type="submit" name="aplicar_correcoes" class="header-btn" title="Aplicar Correções em Todos os Produtos" onclick="return confirm('Deseja aplicar todas as correções nos produtos? Esta ação substituirá todas as palavras incorretas pelas corrigidas em todos os produtos.')">🔁</button>
            </form>
        </div>
    </header>

    <?php if (!empty($mensagem)): ?>
        <div class="message <?php echo $tipo_mensagem; ?>">
            <?php echo $mensagem; ?>
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat-item">
            <div class="stat-number"><?php echo $total_incorretas; ?></div>
            <div>Palavras Incorretas</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $total_corrigidas; ?></div>
            <div>Palavras Corrigidas</div>
        </div>
    </div>

    <div class="filtros">
        <form method="GET">
            <select name="status" onchange="this.form.submit()">
                <option value="incorretas" <?php echo $filtro_status === 'incorretas' ? 'selected' : ''; ?>>Palavras Incorretas</option>
                <option value="corrigidas" <?php echo $filtro_status === 'corrigidas' ? 'selected' : ''; ?>>Palavras Corrigidas</option>
            </select>
        </form>
    </div>

    <?php if (count($palavras) > 0): ?>
    <form method="POST">
        <table>
            <thead>
                <tr>
                    <th width="40%">Palavra Incorreta</th>
                    <th width="40%">Palavra Corrigida</th>
                    <th width="20%">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($palavras as $palavra): ?>
                <tr>
                    <td><?php echo htmlspecialchars($palavra['incorreto']); ?></td>
                    <td>
                        <input type="text" name="corrigido[<?php echo $palavra['id']; ?>]" 
                               value="<?php echo htmlspecialchars($palavra['corrigido'] ?? ''); ?>" 
                               placeholder="Digite a correção...">
                    </td>
                    <td>
                        <button type="submit" name="atualizar_palavras" class="btn-action">💾 Salvar</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="form-actions">
            <button type="submit" name="atualizar_palavras" class="btn-action">💾 Salvar Todas as Alterações</button>
        </div>
    </form>
    <?php else: ?>
        <p style="text-align: center; margin-top: 20px;">
            <?php echo $filtro_status === 'incorretas' ? 'Nenhuma palavra incorreta encontrada.' : 'Nenhuma palavra corrigida encontrada.'; ?>
        </p>
    <?php endif; ?>
</body>
</html>