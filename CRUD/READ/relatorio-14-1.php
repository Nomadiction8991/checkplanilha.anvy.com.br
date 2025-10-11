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
$sql = "SELECT 
            pc.id,
            pc.tipo_ben,
            pc.complemento,
            pc.possui_nota,
            pc.imprimir_14_1,
            tb.codigo as tipo_codigo,
            tb.descricao as tipo_descricao,
            d.descricao as dependencia_descricao
        FROM produtos_cadastro pc
        LEFT JOIN tipos_bens tb ON pc.id_tipo_ben = tb.id
        LEFT JOIN dependencias d ON pc.id_dependencia = d.id
        WHERE pc.imprimir_14_1 = 1 AND pc.id_planilha = :id_planilha
        ORDER BY pc.id ASC";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':id_planilha', $id_planilha);
$stmt->execute();
$produtos = $stmt->fetchAll();

// Fechar conexão
$conn->close();

// As variáveis $produtos e $comum_planilha estarão disponíveis para o HTML
?>