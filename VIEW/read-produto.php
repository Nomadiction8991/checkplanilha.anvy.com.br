<?php
// Inclui o arquivo PHP que contém a lógica
require_once '../CRUD/READ/produto.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Read Produtos</title>
    <link rel="stylesheet" href="../STYLE/read-produto.css">
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="menu-create.php?id_planilha=<?php echo $id_planilha; ?>" class="voltar">
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
                        <td class="centered"><?php echo htmlspecialchars($produto['id']); ?></td>
                        <td class="centered">
                            <?php 
                            echo htmlspecialchars($produto['tipo_codigo'] . ' - ' . $produto['tipo_descricao']);
                            echo ' [' . htmlspecialchars($produto['tipo_ben']) . ']';
                            echo ' ' . htmlspecialchars($produto['complemento']);
                            if (!empty($produto['dependencia_descricao'])) {
                                echo ' (' . htmlspecialchars($produto['dependencia_descricao']) . ')';
                            }
                            ?>
                        </td>
                        <td class="centered">
                            <div class="status-container">
                                <?php if ($produto['possui_nota'] == 1): ?>
                                    <div class="status-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#F19E39"><path d="M320-440h320v-80H320v80Zm0 120h320v-80H320v80Zm0 120h200v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>
                                    </div>
                                <?php endif; ?>
                                <?php if ($produto['imprimir_doacao'] == 1): ?>
                                    <div class="status-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5985E1"><path d="M640-640v-120H320v120h-80v-200h480v200h-80Zm-480 80h640-640Zm560 100q17 0 28.5-11.5T760-500q0-17-11.5-28.5T720-540q-17 0-28.5 11.5T680-500q0 17 11.5 28.5T720-460Zm-80 260v-160H320v160h320Zm80 80H240v-160H80v-240q0-51 35-85.5t85-34.5h560q51 0 85.5 34.5T880-520v240H720v160Zm80-240v-160q0-17-11.5-28.5T760-560H200q-17 0-28.5 11.5T160-520v160h80v-80h480v80h80Z"/></svg>
                                    </div>
                                <?php endif; ?>
                                <?php if ($produto['possui_nota'] == 0 && $produto['imprimir_doacao'] == 0): ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="centered">
                            <div class="acoes-container">
                                <a href="editar-produto.php?id=<?php echo $produto['id']; ?>&id_planilha=<?php echo $id_planilha; ?>" class="btn-acao btn-editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#75FB4C"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/></svg>
                                </a>
                                <a href="excluir-produto.php?id=<?php echo $produto['id']; ?>&id_planilha=<?php echo $id_planilha; ?>" class="btn-acao btn-excluir" onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#EA3323"><path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/></svg>
                                </a>
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