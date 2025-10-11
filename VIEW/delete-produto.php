<?php
// Inclui o arquivo PHP que contém a lógica do delete
require_once '../CRUD/DELETE/produto.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Produto</title>
    <link rel="stylesheet" href="../STYLE/create-produto.css">
    <link rel="stylesheet" href="../STYLE/delete-produto.css">
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="read-produto.php?id_planilha=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(); ?>" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px"fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z" /></svg>
            </a>
            <h1>Excluir Produto</h1>
        </section>
    </header>
    
    <section class="conteudo">
        <div class="form-container">
            <div class="warning-message">
                ⚠️ Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.
            </div>
            
            <?php if (!empty($erros)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <strong>Erros encontrados:</strong>
                    <ul style="margin: 8px 0 0 20px;">
                        <?php foreach ($erros as $erro): ?>
                            <li><?php echo htmlspecialchars($erro); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="form-produto">
                <div class="form-group">
                    <label for="id_tipo_ben" class="required">Tipos de Bens</label>
                    <select id="id_tipo_ben" name="id_tipo_ben" class="form-control select" disabled>
                        <option value="">Selecione um tipo de bem</option>
                        <?php 
                        // Buscar o tipo específico do produto
                        $sql_tipo = "SELECT id, codigo, descricao FROM tipos_bens WHERE id = :id";
                        $stmt_tipo = $conexao->prepare($sql_tipo);
                        $stmt_tipo->bindValue(':id', $produto['id_tipo_ben']);
                        $stmt_tipo->execute();
                        $tipo_produto = $stmt_tipo->fetch();
                        ?>
                        <?php if ($tipo_produto): ?>
                            <option value="<?php echo $tipo_produto['id']; ?>" selected>
                                <?php echo htmlspecialchars($tipo_produto['codigo'] . ' - ' . $tipo_produto['descricao']); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tipo_ben" class="required">Bem</label>
                    <select id="tipo_ben" name="tipo_ben" class="form-control select" disabled>
                        <option value="<?php echo htmlspecialchars($produto['tipo_ben']); ?>" selected>
                            <?php echo htmlspecialchars($produto['tipo_ben']); ?>
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="complemento" class="required">Complemento</label>
                    <textarea id="complemento" name="complemento" class="form-control" 
                              rows="3" disabled><?php echo htmlspecialchars($produto['complemento']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="id_dependencia">Dependência</label>
                    <select id="id_dependencia" name="id_dependencia" class="form-control select" disabled>
                        <option value="">Selecione uma dependência</option>
                        <?php if (!empty($produto['id_dependencia'])): ?>
                            <option value="<?php echo $produto['id_dependencia']; ?>" selected>
                                <?php echo htmlspecialchars($produto['dependencia_descricao']); ?>
                            </option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="possui_nota" name="possui_nota" value="1" <?php echo ($produto['possui_nota'] == 1) ? 'checked' : ''; ?> disabled>
                            <label for="possui_nota">Possui Nota</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="imprimir_doacao" name="imprimir_doacao" value="1" <?php echo ($produto['imprimir_doacao'] == 1) ? 'checked' : ''; ?> disabled>
                            <label for="imprimir_doacao">Imprimir Doação</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit btn-delete">Confirmar Exclusão</button>
            </form>
        </div>
    </section>
</body>
</html>