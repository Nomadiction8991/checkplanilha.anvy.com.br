<?php
ini_set('display_errors', 0);
error_reporting(E_ERROR);

require_once __DIR__ . '/../../auth.php'; // Autenticação
require_once __DIR__ . '/../conexao.php';

// Apenas admins podem acessar
if (!isAdmin()) {
    header('Location: ../../../index.php');
    exit;
}

$pagina = isset($_GET['pagina']) ? max(1,(int)$_GET['pagina']) : 1;
$limite = 20;
$offset = ($pagina - 1) * $limite;

try {
    // Verificar se conexão existe
    if (!$conexao) {
        throw new Exception("Falha na conexão com o banco de dados");
    }

    // Contagem total
    $sql_count = "SELECT COUNT(*) FROM dependencias";
    $total_registros = (int)$conexao->query($sql_count)->fetchColumn();
    $total_paginas = (int)ceil($total_registros / $limite);

    // Buscar página de dependências
    $sql = "SELECT * FROM dependencias ORDER BY codigo ASC LIMIT :limite OFFSET :offset";
    $stmt = $conexao->prepare($sql);
    $stmt->bindValue(':limite',$limite,PDO::PARAM_INT);
    $stmt->bindValue(':offset',$offset,PDO::PARAM_INT);
    $stmt->execute();
    $dependencias = $stmt->fetchAll();
} catch (Throwable $e) {
    // Em caso de erro, definir valores padrão
    $dependencias = [];
    $total_registros = 0;
    $total_paginas = 0;
    $pagina = 1;
    error_log("Erro ao carregar dependências: " . $e->getMessage());
}
?></content>
<parameter name="filePath">/home/weverton/Documentos/Github-Gitlab/GitHub/checkplanilha.anvy.com.br/CRUD/READ/dependencia.php