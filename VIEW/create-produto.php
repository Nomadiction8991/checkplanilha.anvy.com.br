<?php
$id_planilha = $_GET['id_planilha'] ?? null;

if (!$id_planilha) {
    header('Location: menu-create.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Produto</title>
    <link rel="stylesheet" href="../STYLE/read-produto.css">
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="read-produto.php?id_planilha=<?php echo $id_planilha; ?>" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px"fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z" /></svg>
            </a>
            <h1>Cadastrar Produto</h1>
        </section>
    </header>
    <section class="conteudo">
        <!-- Formulário de cadastro aqui -->
        <p>Formulário de cadastro para a planilha ID: <?php echo $id_planilha; ?></p>
    </section>
</body>
</html>