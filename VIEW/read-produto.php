<?php
// Inclui o arquivo PHP que contém a lógica
require_once '../../CRUD/READ/produto.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Read Produtos</title>
    <link rel="stylesheet" href="../../STYLE/read-produto.css">
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="../menu-create.php?id_planilha=<?php echo $id_planilha; ?>" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px"fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z" /></svg>
            </a>
            <h1>Read Produtos</h1>
        </section>
        <section class="acoes">
            <a href="../CREATE/create-produto.php?id_planilha=<?php echo $id_planilha; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M450-450H200v-60h250v-250h60v250h250v60H510v250h-60v-250Z"/></svg>
            </a>
        </section>
    </header>
    <section class="conteudo">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($produtos): ?>
                    <?php foreach ($produtos as $produto): ?>
                    <tr>
                        <td data-label="ID"><?php echo htmlspecialchars($produto['id']); ?></td>
                        <td data-label="Descrição">
                            <?php 
                            echo htmlspecialchars($produto['tipo_codigo'] . ' - ' . $produto['tipo_descricao']);
                            echo ' [' . htmlspecialchars($produto['tipo_ben']) . ']';
                            echo ' ' . htmlspecialchars($produto['complemento']);
                            if (!empty($produto['dependencia_descricao'])) {
                                echo ' (' . htmlspecialchars($produto['dependencia_descricao']) . ')';
                            }
                            ?>
                        </td>
                        <td data-label="Status">
                            <?php if ($produto['possui_nota'] == 1): ?>
                                <span class="status-badge status-nota">Nota</span>
                            <?php endif; ?>
                            <?php if ($produto['imprimir_doacao'] == 1): ?>
                                <span class="status-badge status-imprimir">Imprimir</span>
                            <?php endif; ?>
                            <?php if ($produto['possui_nota'] == 0 && $produto['imprimir_doacao'] == 0): ?>
                                <span>-</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="Ações">
                            <div class="acoes-links">
                                <a href="../UPDATE/editar-produto.php?id=<?php echo $produto['id']; ?>&id_planilha=<?php echo $id_planilha; ?>" class="btn-acao btn-editar">Editar</a>
                                <a href="../DELETE/excluir-produto.php?id=<?php echo $produto['id']; ?>&id_planilha=<?php echo $id_planilha; ?>" class="btn-acao btn-excluir" onclick="return confirm('Tem certeza que deseja excluir este produto?')">Excluir</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">
                            Nenhum produto cadastrado para esta planilha.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</body>
</html>