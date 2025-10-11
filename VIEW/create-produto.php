<?php
$id_planilha = $_GET['id_planilha'] ?? null;

if (!$id_planilha) {
    header('Location: menu-create.php');
    exit;
}

// Inclui o arquivo PHP que contém a lógica do create
require_once '../CRUD/CREATE/produto.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Produto</title>
    <link rel="stylesheet" href="../STYLE/create-produto.css">
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="read-produto.php?id_planilha=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(); ?>" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px"fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z" /></svg>
            </a>
            <h1>Cadastrar Produto</h1>
        </section>
    </header>
    
    <section class="conteudo">
        <div class="form-container">
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
                    <label for="id_tipo_ben" class="required">Tipo de Bem</label>
                    <select id="id_tipo_ben" name="id_tipo_ben" class="form-control select" required>
                        <option value="">Selecione um tipo de bem</option>
                        <?php foreach ($tipos_bens as $tipo): ?>
                            <option value="<?php echo $tipo['id']; ?>" <?php echo (isset($_POST['id_tipo_ben']) && $_POST['id_tipo_ben'] == $tipo['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['descricao']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tipo_ben" class="required">Código do Bem</label>
                    <input type="text" id="tipo_ben" name="tipo_ben" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['tipo_ben'] ?? ''); ?>" 
                           placeholder="Digite o código do bem" required>
                </div>
                
                <div class="form-group">
                    <label for="complemento" class="required">Complemento</label>
                    <textarea id="complemento" name="complemento" class="form-control" 
                              rows="3" placeholder="Digite o complemento do produto" required><?php echo htmlspecialchars($_POST['complemento'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="id_dependencia">Dependência</label>
                    <select id="id_dependencia" name="id_dependencia" class="form-control select">
                        <option value="">Selecione uma dependência</option>
                        <?php foreach ($dependencias as $dep): ?>
                            <option value="<?php echo $dep['id']; ?>" <?php echo (isset($_POST['id_dependencia']) && $_POST['id_dependencia'] == $dep['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dep['descricao']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="possui_nota" name="possui_nota" value="1" <?php echo (isset($_POST['possui_nota']) && $_POST['possui_nota'] == 1) ? 'checked' : ''; ?>>
                            <label for="possui_nota">Possui Nota</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="imprimir_doacao" name="imprimir_doacao" value="1" <?php echo (isset($_POST['imprimir_doacao']) && $_POST['imprimir_doacao'] == 1) ? 'checked' : ''; ?>>
                            <label for="imprimir_doacao">Imprimir Doação</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">Cadastrar Produto</button>
            </form>
        </div>
    </section>

    <script>
        // Validação básica do formulário
        document.getElementById('form-produto').addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios.');
            }
        });

        // Remover classe de erro quando o usuário começar a digitar
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
            });
        });
    </script>
</body>
</html>