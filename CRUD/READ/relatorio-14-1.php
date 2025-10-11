<?php
// Incluir arquivo de conexão
require_once '../CRUD/conexao.php';

// Pegar o ID da planilha via GET
$id_planilha = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Buscar dados da planilha para obter o valor da coluna "comum"
$sql_planilha = "SELECT comum FROM planilhas WHERE id = :id_planilha";
$stmt_planilha = $conn->prepare($sql_planilha);
$stmt_planilha->bindValue(':id_planilha', $id_planilha);
$stmt_planilha->execute();
$planilha = $stmt_planilha->fetch();

$comum_planilha = $planilha ? $planilha['comum'] : '';

// Consultar produtos que devem imprimir o relatório 14.1
$sql = "SELECT * FROM produtos_cadastro WHERE imprimir_14_1 = 1";
$result = $conn->query($sql);

// Verificar se há resultados
if ($result->num_rows > 0) {
    $count = 0;
    // Loop através de cada produto
    while($row = $result->fetch_assoc()) {
        $count++;
        // Incluir o HTML do formulário para cada produto
        include 'relatorio-14-1-template.php';
        
        // Adicionar quebra de página entre os formulários (exceto no último)
        if ($count < $result->num_rows) {
            echo '<div style="page-break-after: always;"></div>';
        }
    }
} else {
    echo "Nenhum produto encontrado para impressão do relatório 14.1.";
}

// Fechar conexão
$conn->close();
?>