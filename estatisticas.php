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

echo "<div style='display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;'>";
echo "<div style='background: #f8f9fa; padding: 15px; text-align: center; border-left: 4px solid #007bff;'><h3>Total</h3><p style='font-size: 24px; font-weight: bold;'>$total</p></div>";
echo "<div style='background: #f8f9fa; padding: 15px; text-align: center; border-left: 4px solid #28a745;'><h3>Checados</h3><p style='font-size: 24px; font-weight: bold; color: green;'>$checados</p></div>";
echo "<div style='background: #f8f9fa; padding: 15px; text-align: center; border-left: 4px solid #ffc107;'><h3>Pendentes</h3><p style='font-size: 24px; font-weight: bold; color: orange;'>$naoChecados</p></div>";
echo "<div style='background: #f8f9fa; padding: 15px; text-align: center; border-left: 4px solid #007bff;'><h3>Progresso</h3><p style='font-size: 24px; font-weight: bold; color: blue;'>$percentual%</p></div>";
echo "</div>";

echo "<div style='width: 100%; background-color: #f0f0f0; border-radius: 5px; margin: 10px 0;'>";
echo "<div style='height: 20px; background-color: #28a745; border-radius: 5px; width: $percentual%;'></div>";
echo "</div>";
echo "<p style='text-align: center; margin-top: 10px;'>$checados de $total itens concluídos</p>";
?>