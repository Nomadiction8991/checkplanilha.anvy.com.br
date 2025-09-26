<?php
require_once 'config.php';

$database = new Database();
$conn = $database->getConnection();

$pagina = $_GET['pagina'] ?? 1;
$itensPorPagina = 50;
$offset = ($pagina - 1) * $itensPorPagina;

// Total de itens
$queryTotal = "SELECT COUNT(*) as total FROM planilha";
$stmtTotal = $conn->prepare($queryTotal);
$stmtTotal->execute();
$totalItens = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
$totalPaginas = ceil($totalItens / $itensPorPagina);

// Buscar itens com paginação
$query = "SELECT * FROM planilha ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Itens</title>
</head>
<body>
    <h1>Lista de Itens (<?php echo $totalItens; ?> itens no total)</h1>
    <a href="index.php">← Voltar</a>
    
    <?php if (empty($itens)): ?>
        <p>Nenhum item encontrado.</p>
    <?php else: ?>
        <table border="1" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Localidade</th>
                    <th>Status</th>
                    <th>Checado</th>
                    <th>Data Checagem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr style="<?php echo $item['checado'] ? 'background-color: #d4edda;' : ''; ?>">
                        <td><?php echo htmlspecialchars($item['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($item['nome'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['localidade'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['status'] ?? 'N/A'); ?></td>
                        <td><?php echo $item['checado'] ? '✓' : '✗'; ?></td>
                        <td><?php echo $item['data_checagem'] ? date('d/m/Y H:i', strtotime($item['data_checagem'])) : '-'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Paginação -->
        <div style="margin-top: 20px;">
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <a href="?pagina=<?php echo $i; ?>" 
                   style="padding: 5px 10px; border: 1px solid #ccc; margin: 0 2px; 
                          <?php echo $i == $pagina ? 'background-color: #007bff; color: white;' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</body>
</html>