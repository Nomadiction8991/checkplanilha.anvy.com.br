<?php
require_once '../CRUD/CREATE/importar-planilha.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Planilha - Anvy</title>
    <link rel="stylesheet" href="../STYLE/importar-planilha.css">
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="../index.php" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF">
                    <path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z"/>
                </svg>
            </a>
            <h1>Importar Planilha</h1>
        </section>
    </header>

    <section class="conteudo">
        <?php if (!empty($mensagem)): ?>
            <div class="message <?php echo $tipo_mensagem; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Informações da Planilha</h2>
            <form method="POST" enctype="multipart/form-data">
                <!-- Campo Descrição -->
                <div class="form-group">
                    <label for="descricao">Descrição da Planilha:</label>
                    <input type="text" id="descricao" name="descricao" 
                           value="<?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?>" 
                           required placeholder="Digite um nome para identificar esta planilha">
                </div>

                <!-- Campo Comum -->
                <div class="form-group">
                    <label for="comum">Localização Comum:</label>
                    <input type="text" id="comum" name="comum" 
                           value="<?php echo htmlspecialchars($_POST['comum'] ?? 'D16'); ?>" 
                           required placeholder="Ex: D16">
                    <small>Localização padrão que será salva na planilha</small>
                </div>

                <!-- Campo Arquivo -->
                <div class="form-group">
                    <label for="arquivo">Arquivo CSV:</label>
                    <input type="file" id="arquivo" name="arquivo" accept=".csv" required>
                    <small>Selecione o arquivo CSV para importação</small>
                </div>

                <!-- Configurações de Importação -->
                <div class="card">
                    <h2>Configurações de Importação</h2>
                    
                    <!-- Linhas a pular -->
                    <div class="form-group">
                        <label for="linhas_pular">Linhas iniciais a pular:</label>
                        <input type="number" id="linhas_pular" name="linhas_pular" 
                               value="<?php echo $_POST['linhas_pular'] ?? 25; ?>" min="0" required>
                        <small>Número de linhas do cabeçalho que devem ser ignoradas</small>
                    </div>

                    <!-- Mapeamento de Colunas -->
                    <h3 style="margin: 20px 0 10px 0; font-size: 14px; color: #495057;">Mapeamento de Colunas</h3>
                    <p style="margin-bottom: 15px; font-size: 13px; color: #666;">Defina a letra da coluna para cada campo:</p>

                    <div class="mapeamento-grid">
                        <div class="mapeamento-item">
                            <span class="mapeamento-label">Código:</span>
                            <input type="text" class="mapeamento-input" name="codigo" 
                                   value="<?php echo $_POST['codigo'] ?? 'A'; ?>" maxlength="2" required>
                        </div>

                        <div class="mapeamento-item">
                            <span class="mapeamento-label">Nome:</span>
                            <input type="text" class="mapeamento-input" name="nome" 
                                   value="<?php echo $_POST['nome'] ?? 'D'; ?>" maxlength="2" required>
                        </div>

                        <div class="mapeamento-item">
                            <span class="mapeamento-label">Dependência:</span>
                            <input type="text" class="mapeamento-input" name="dependencia" 
                                   value="<?php echo $_POST['dependencia'] ?? 'P'; ?>" maxlength="2" required>
                        </div>
                    </div>
                </div>

                <div class="botoes">
                    <a href="../index.php" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                            <path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z"/>
                        </svg>
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                            <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
                        </svg>
                        Importar Planilha
                    </button>
                </div>
            </form>
        </div>
    </section>
</body>
</html>