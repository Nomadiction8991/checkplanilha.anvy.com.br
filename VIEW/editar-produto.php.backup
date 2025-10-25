<?php
require_once '../CRUD/UPDATE/editar-produto.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Produto</title>
    <link rel="stylesheet" href="../STYLE/base.css">
    <link rel="stylesheet" href="../STYLE/editar-produto.css">
</head>
<body>
    <header class="cabecalho">
        <div class="titulo">
            <a href="<?php echo getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status); ?>" class="voltar" title="Voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="m313-440 224 224-57 56-320-320 320-320 57 56-224 224h487v80H313Z"/></svg>
            </a>
            <h1>Editar Produto</h1>
        </div>
    </header>

    <div class="conteudo">
        <?php if (!empty($mensagem)): ?>
            <div class="message <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- Informações do Produto -->
        <div class="product-card">
            <h3>Informações Atuais do Produto</h3>
            
            <div class="product-field">
                <div class="field-label">Código:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></div>
            </div>

            <div class="product-field">
                <div class="field-label">Nome Atual:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['nome'] ?? ''); ?></div>
            </div>

            <div class="product-field">
                <div class="field-label">Dependência Atual:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['dependencia'] ?? ''); ?></div>
            </div>
        </div>

        <div class="info-box">
            <strong>Informação:</strong> Se os campos abaixo permanecerem em branco, nenhuma alteração será feita.
            <br><strong>Atenção:</strong> Ao editar o produto, ele será automaticamente marcado para impressão de etiqueta.
        </div>

        <!-- Formulário de Edição -->
        <form method="POST" action="">
            <!-- Campos hidden para preservar filtros -->
            <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
            <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
            <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
            <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">

            <!-- Campo Novo Nome -->
            <div class="form-group campo-edicao">
                <label for="novo_nome" class="form-label">Novo Nome (deixe em branco para não alterar):</label>
                <input type="text" id="novo_nome" name="novo_nome" class="form-control" 
                       value="<?php echo htmlspecialchars($novo_nome ?? ''); ?>" 
                       placeholder="Digite o novo nome...">
            </div>

            <!-- Campo Nova Dependência -->
            <div class="form-group campo-edicao">
                <label for="nova_dependencia" class="form-label">Nova Dependência (deixe em branco para não alterar):</label>
                <select id="nova_dependencia" name="nova_dependencia" class="form-control">
                    <option value="">-- Selecione uma nova dependência --</option>
                    <?php foreach ($dependencia_options as $dep): ?>
                        <option value="<?php echo htmlspecialchars($dep); ?>" 
                            <?php echo (isset($nova_dependencia) && $nova_dependencia === $dep) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dep); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        </form>

        <!-- Botão para Limpar Edições -->
        <div class="limpar-edicoes">
            <a href="../CRUD/DELETE/editar-produto.php?id=<?php echo $id_planilha; ?>&id_produto=<?php echo $id_produto; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>" 
               class="btn btn-danger" 
               onclick="return confirm('Tem certeza que deseja limpar as edições deste produto?')">
                🗑️ Limpar Edições
            </a>
        </div>
    </div>
</body>
</html>