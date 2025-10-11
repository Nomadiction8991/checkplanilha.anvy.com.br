<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Observações</title>
    <link rel="stylesheet" href="../STYLE/observacao-produto.css">
</head>
<body>
    <header class="cabecalho">
        <div class="titulo">
            <a href="<?php echo getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status); ?>" class="voltar" title="Voltar">←</a>
            <h1>Editar Observações</h1>
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
            <h3>Informações do Produto</h3>
            
            <div class="product-field">
                <div class="field-label">Código:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['codigo'] ?? ''); ?></div>
            </div>

            <div class="product-field">
                <div class="field-label">Nome:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['nome'] ?? ''); ?></div>
            </div>

            <div class="product-field">
                <div class="field-label">Dependência:</div>
                <div class="field-value"><?php echo htmlspecialchars($produto['dependencia'] ?? ''); ?></div>
            </div>

            <div class="product-field">
                <div class="field-label">Status do Produto:</div>
                <div class="status-info">
                    <?php if ($check['checado'] == 1): ?>
                        <span class="status-badge status-checado">✅ Checado</span>
                    <?php endif; ?>
                    <?php if (!empty($check['observacoes'])): ?>
                        <span class="status-badge status-observacao">📜 Com Observações</span>
                    <?php endif; ?>
                    <?php if ($check['dr'] == 1): ?>
                        <span class="status-badge status-dr">📦 No DR</span>
                    <?php endif; ?>
                    <?php if ($check['imprimir'] == 1): ?>
                        <span class="status-badge status-imprimir">🏷️ Para Imprimir</span>
                    <?php endif; ?>
                    <?php if ($check['checado'] == 0 && empty($check['observacoes']) && $check['dr'] == 0 && $check['imprimir'] == 0): ?>
                        <span class="status-badge status-pendente">⏳ Pendente</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Formulário de Edição -->
        <form method="POST" action="">
            <!-- Campos hidden para preservar filtros -->
            <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
            <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
            <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
            <input type="hidden" name="filtro_codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">

            <!-- Campo Observações -->
            <div class="form-group">
                <label for="observacoes" class="form-label">Observações:</label>
                <textarea id="observacoes" name="observacoes" class="form-control" rows="6" 
                          placeholder="Digite observações sobre este produto..."><?php echo htmlspecialchars($check['observacoes'] ?? ''); ?></textarea>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary">Salvar Observações</button>
        </form>
    </div>
</body>
</html>