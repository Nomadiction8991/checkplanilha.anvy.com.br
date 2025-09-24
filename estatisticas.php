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

// Percentual
$percentual = $total > 0 ? round(($checados / $total) * 100, 2) : 0;

echo "<p>Total de itens: $total</p>";
echo "<p>Itens checados: $checados</p>";
echo "<p>Progresso: $percentual%</p>";
echo "<div class='progress'><div class='progress-bar' style='width: $percentual%'></div></div>";
?>