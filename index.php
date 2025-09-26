<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Checklist - Bens Imobilizados</title>
</head>
<body>
    <h1>Sistema de Checklist - Bens Imobilizados</h1>
    
    <!-- Seção de Upload -->
    <div>
        <h3>Upload da Planilha</h3>
        <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
            <input type="file" name="planilha" accept=".csv" required>
            <button type="submit">Enviar Planilha</button>
        </form>
    </div>

    <!-- Seção de Busca -->
    <div>
        <h3>Checklist de Produtos</h3>
        <form action="check_produto.php" method="post">
            <input type="text" name="codigo" placeholder="Digite ou escaneie o código do produto..." required autofocus>
            <button type="submit">Marcar Check</button>
        </form>
        
        <?php
        // Mostrar resultado da última operação
        if (isset($_GET['resultado'])) {
            $tipo = $_GET['tipo'] ?? 'erro';
            $mensagem = $_GET['resultado'];
            echo "<div style='padding: 10px; margin: 10px 0; border: 1px solid;'>$mensagem</div>";
        }
        ?>
    </div>

    <!-- Link para lista de itens -->
    <div>
        <h3>Lista de Itens</h3>
        <a href="lista_itens.php">Ver Lista Completa de Itens</a>
    </div>

    <!-- Estatísticas -->
    <div>
        <h3>Estatísticas</h3>
        <?php include 'estatisticas.php'; ?>
    </div>
</body>
</html>