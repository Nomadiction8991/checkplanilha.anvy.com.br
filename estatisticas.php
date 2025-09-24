<?php
require_once 'config.php';

$database = new Database();
$conn = $database->getConnection();

// Total de itens
$queryTotal = "SELECT COUNT(*) as total FROM planilha";
$stmtTotal = $conn->prepare($queryTotal);
$stmtTotal->execute();
$total = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

// Itens checados
$queryChecados = "SELECT COUNT(*) as checados FROM planilha WHERE checado = 1";
$stmtChecados = $conn->prepare($queryChecados);
$stmtChecados->execute();
$checados = $stmtChecados->fetch(PDO::FETCH_ASSOC)['checados'];

// Itens não checados
$naoChecados = $total - $checados;

// Percentual
$percentual = $total > 0 ? round(($checados / $total) * 100, 2) : 0;

echo "<div class='stats-grid'>";
echo "<div class='stat-card'><h3>Total</h3><p style='font-size: 24px; font-weight: bold;'>$total</p></div>";
echo "<div class='stat-card'><h3>Checados</h3><p style='font-size: 24px; font-weight: bold; color: green;'>$checados</p></div>";
echo "<div class='stat-card'><h3>Pendentes</h3><p style='font-size: 24px; font-weight: bold; color: orange;'>$naoChecados</p></div>";
echo "<div class='stat-card'><h3>Progresso</h3><p style='font-size: 24px; font-weight: bold; color: blue;'>$percentual%</p></div>";
echo "</div>";

echo "<div class='progress'><div class='progress-bar' style='width: $percentual%'></div></div>";
echo "<p style='text-align: center; margin-top: 10px;'>$checados de $total itens concluídos</p>";
?>