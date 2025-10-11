<?php
$id_planilha = $_GET['id_planilha'] ?? null;

if (!$id_planilha) {
    header('Location: ../index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Create</title>
    <link rel="stylesheet" href="../STYLE/menu-create.css">
</head>

<body>
    <header class="cabecalho">
        <a href="../visualizar_planilha.php?id=<?php echo $id_planilha; ?>" class="voltar">
            <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px"fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z" /></svg>
        </a>
        <h1>Menu Create</h1>
    </header>
    <section class="conteudo">
        <nav class="menu">
            <a class="opcao op1" href="READ/read-produto.php?id_planilha=<?php echo $id_planilha; ?>">Cadastrar Produto</a>
            <a class="opcao op2" href="#">Em Desenvolvimento</a>
            <a class="opcao op3" href="#">Em Desenvolvimento</a>
            <a class="opcao op4" href="#">Em Desenvolvimento</a>
            <a class="opcao op5" href="#">Em Desenvolvimento</a>
        </nav>
    </section>
</body>
</html>