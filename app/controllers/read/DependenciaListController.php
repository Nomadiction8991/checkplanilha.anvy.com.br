<?php
declare(strict_types=1);
// CRUD/READ/dependencia.php - implementaÃ§Ã£o limpa

require_once dirname(__DIR__, 2) . '/bootstrap.php';

if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

try {
    if (!$conexao) {
        throw new Exception('Sem conexÃ£o com o banco de dados');
    }

    $sql_count = 'SELECT COUNT(*) FROM dependencias';
    $total_registros = (int) $conexao->query($sql_count)->fetchColumn();
    $total_paginas = (int) ceil($total_registros / $limite);

    $sql = "SELECT id, descricao FROM dependencias ORDER BY descricao ASC, id ASC LIMIT :limite OFFSET :offset";
    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $dependencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $dependencias = [];
    $total_registros = 0;
    $total_paginas = 0;
    $pagina = 1;
    error_log('Erro ao carregar dependÃªncias: ' . $e->getMessage());
}

?>


