<?php
require_once 'config.php';

header('Content-Type: application/json');

$database = new Database();
$conn = $database->getConnection();

$ultima = $_GET['ultima'] ?? 0;

// Buscar itens checados recentemente
$query = "SELECT * FROM planilha WHERE checado = 1 AND UNIX_TIMESTAMP(data_checagem) > ? ORDER BY data_checagem DESC LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute([$ultima]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html = '';
foreach ($itens as $item) {
    $html .= '<div class="item-checado">';
    $html .= '<strong>' . htmlspecialchars($item['codigo']) . '</strong> - ';
    $html .= htmlspecialchars($item['nome']) . ' - ';
    $html .= date('d/m/Y H:i', strtotime($item['data_checagem']));
    $html .= '</div>';
}

echo json_encode([
    'html' => $html,
    'ultimaAtualizacao' => time()
]);
?>