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
$query = "SELECT codigo, nome, nome_novo, dependencia, status, checado, data_checagem, usuario_checagem 
          FROM planilha ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $itensPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gerar PDF
if (isset($_GET['pdf'])) {
    require_once 'vendor/autoload.php';
    
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML('
        <h1>Lista de Itens - Bens Imobilizados</h1>
        <table border="1" style="width:100%; border-collapse:collapse;">
        <tr>
            <th>Código</th>
            <th>Nome</th>
            <th>Nome Novo</th>
            <th>Dependência</th>
            <th>Status</th>
            <th>Checado</th>
            <th>Data Checagem</th>
            <th>Usuário</th>
        </tr>
    ');
    
    foreach ($itens as $item) {
        $mpdf->WriteHTML('
            <tr>
                <td>'.htmlspecialchars($item['codigo']).'</td>
                <td>'.htmlspecialchars($item['nome'] ?? 'N/A').'</td>
                <td>'.htmlspecialchars($item['nome_novo'] ?? '').'</td>
                <td>'.htmlspecialchars($item['dependencia'] ?? 'N/A').'</td>
                <td>'.htmlspecialchars($item['status'] ?? 'N/A').'</td>
                <td>'.($item['checado'] ? '✓' : '✗').'</td>
                <td>'.($item['data_checagem'] ? date('d/m/Y H:i', strtotime($item['data_checagem'])) : '-').'</td>
                <td>'.htmlspecialchars($item['usuario_checagem'] ?? '').'</td>
            </tr>
        ');
    }
    
    $mpdf->WriteHTML('</table>');
    $mpdf->Output('lista_itens.pdf', 'D');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Itens</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        h1 { color: #333; margin-bottom: 20px; }
        .actions { margin-bottom: 20px; }
        .btn { padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block; margin-right: 10px; }
        .btn-pdf { background: #dc3545; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; font-size: 14px; }
        th { background: #343a40; color: white; }
        .checado { background: #d4edda; }
        .pagination { display: flex; gap: 5px; flex-wrap: wrap; }
        .page-btn { padding: 8px 12px; border: 1px solid #ddd; background: white; text-decoration: none; color: #333; }
        .page-btn.active { background: #007bff; color: white; }
        @media (max-width: 768px) {
            table { font-size: 12px; }
            th, td { padding: 5px; }
            .btn { display: block; margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Lista de Itens (<?php echo $totalItens; ?> itens)</h1>
        
        <div class="actions">
            <a href="index.php" class="btn">← Voltar</a>
            <a href="?pdf=1" class="btn btn-pdf">📄 Gerar PDF</a>
        </div>
        
        <?php if (empty($itens)): ?>
            <p>Nenhum item encontrado.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Nome Novo</th>
                        <th>Dependência</th>
                        <th>Status</th>
                        <th>Checado</th>
                        <th>Data Checagem</th>
                        <th>Usuário</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr class="<?php echo $item['checado'] ? 'checado' : ''; ?>">
                            <td><?php echo htmlspecialchars($item['codigo']); ?></td>
                            <td><?php echo htmlspecialchars($item['nome'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['nome_novo'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['dependencia'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['status'] ?? 'N/A'); ?></td>
                            <td><?php echo $item['checado'] ? '✓' : '✗'; ?></td>
                            <td><?php echo $item['data_checagem'] ? date('d/m/Y H:i', strtotime($item['data_checagem'])) : '-'; ?></td>
                            <td><?php echo htmlspecialchars($item['usuario_checagem'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Paginação -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <a href="?pagina=<?php echo $i; ?>" class="page-btn <?php echo $i == $pagina ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>