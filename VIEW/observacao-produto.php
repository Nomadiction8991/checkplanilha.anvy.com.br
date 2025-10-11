<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Observações</title>
    <link rel="stylesheet" href="../STYLE/observacao-produto.css">
</head>
<body>
    <!-- CABEÇALHO NO ESTILO DO READ-PRODUTO -->
    <header class="cabecalho">
        <section class="titulo">
            <a href="<?php echo getReturnUrl($id_planilha, $pagina, $filtro_nome, $filtro_dependencia, $filtro_codigo, $filtro_status); ?>" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z"/></svg>
            </a>
            <h1>Editar Observações</h1>
        </section>
    </header>

    <!-- CONTEÚDO NO ESTILO DO READ-PRODUTO -->
    <section class="conteudo">
        <?php if (!empty($mensagem)): ?>
            <div class="message <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <!-- CARD DO PRODUTO NO ESTILO DA TABELA -->
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

        <!-- FORMULÁRIO NO ESTILO DO READ-PRODUTO -->
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
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                    <path d="M840-680v480q0 33-23.5 56.5T760-120H200q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h480l160 160Zm-80 34L646-760H200v560h560v-446ZM480-240q50 0 85-35t35-85q0-50-35-85t-85-35q-50 0-85 35t-35 85q0 50 35 85t85 35ZM240-560h360v-160H240v160Zm-40-86v446-560 114Z"/>
                </svg>
                Salvar Observações
            </button>
        </form>
    </section>
</body>
</html>