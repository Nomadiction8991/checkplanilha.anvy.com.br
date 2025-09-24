<?php
require_once 'config.php';

header('Content-Type: application/json');

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

$html = '';
if (empty($itens)) {
    $html = '<div class="info">Nenhum item encontrado.</div>';
} else {
    $html .= '<table>';
    $html .= '<thead><tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Localidade</th>
                <th>Status</th>
                <th>Checado</th>
                <th>Data Checagem</th>
              </tr></thead>';
    $html .= '<tbody>';
    
    foreach ($itens as $item) {
        $classe = $item['checado'] ? 'checado' : '';
        $checado = $item['checado'] ? '✓' : '✗';
        $dataChecagem = $item['data_checagem'] ? date('d/m/Y H:i', strtotime($item['data_checagem'])) : '-';
        
        $html .= '<tr class="' . $classe . '">';
        $html .= '<td>' . htmlspecialchars($item['codigo']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['nome'] ?? 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($item['localidade'] ?? 'N/A') . '</td>';
        $html .= '<td>' . htmlspecialchars($item['status'] ?? 'N/A') . '</td>';
        $html .= '<td>' . $checado . '</td>';
        $html .= '<td>' . $dataChecagem . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
}

echo json_encode([
    'html' => $html,
    'totalPaginas' => $totalPaginas,
    'paginaAtual' => $pagina,
    'totalItens' => $totalItens
]);
?>