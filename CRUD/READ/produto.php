<?php
require_once '../conexao.php';

$id_planilha = $_GET['id_planilha'] ?? null;

if (!$id_planilha) {
    header('Location: ../../VIEW/menu-create.php');
    exit;
}

// Buscar produtos da planilha
try {
    $sql = "SELECT 
                pc.id,
                pc.tipo_ben,
                pc.complemento,
                pc.possui_nota,
                pc.imprimir_doacao,
                tb.codigo as tipo_codigo,
                tb.descricao as tipo_descricao,
                d.descricao as dependencia_descricao
            FROM produtos_cadastro pc
            LEFT JOIN tipos_bens tb ON pc.id_tipo_ben = tb.id
            LEFT JOIN dependencias d ON pc.id_dependencia = d.id
            WHERE pc.id_planilha = :id_planilha
            ORDER BY pc.id DESC";
    
    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':id_planilha', $id_planilha);
    $stmt->execute();
    $produtos = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erro ao carregar produtos: " . $e->getMessage());
}

// Preparar dados para a view
$dados_view = [
    'id_planilha' => $id_planilha,
    'produtos' => $produtos
];

// Incluir a view
include '../../VIEW/read-produto.php';
?>