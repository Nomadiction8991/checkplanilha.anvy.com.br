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
    <link rel="shortcut icon" href="../logo.png" type="image/x-icon">
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
            <form method="POST" enctype="multipart/form-data">
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

                    <!-- Campo Localização Comum -->
                    <div class="form-group">
                        <label for="localizacao_comum">Localização da Célula Comum:</label>
                        <input type="text" id="localizacao_comum" name="localizacao_comum" 
                               value="<?php echo htmlspecialchars($_POST['localizacao_comum'] ?? 'D16'); ?>" 
                               required placeholder="Ex: D16">
                        <small>Localização da célula no arquivo CSV que contém o valor comum (ex: D16)</small>
                    </div>

                    <!-- Campo Localização Data Posição -->
                    <div class="form-group">
                        <label for="localizacao_data_posicao">Localização da Célula Data Posição:</label>
                        <input type="text" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                               value="<?php echo htmlspecialchars($_POST['localizacao_data_posicao'] ?? 'D13'); ?>" 
                               required placeholder="Ex: D13">
                        <small>Localização da célula no arquivo CSV que contém a data de posição (ex: D13)</small>
                    </div>

                    <!-- Configurações de Endereço e CNPJ -->
                    <h3 style="margin: 20px 0 10px 0; font-size: 14px; color: #495057;">Configurações de Endereço e CNPJ</h3>

                    <!-- Campo Localização Endereço -->
                    <div class="form-group">
                        <label for="localizacao_endereco">Localização da Célula Endereço:</label>
                        <input type="text" id="localizacao_endereco" name="localizacao_endereco" 
                               value="<?php echo htmlspecialchars($_POST['localizacao_endereco'] ?? 'A4'); ?>" 
                               required placeholder="Ex: A4">
                        <small>Localização da célula no arquivo CSV que contém o endereço (ex: A4)</small>
                    </div>

                    <!-- Campo Localização CNPJ -->
                    <div class="form-group">
                        <label for="localizacao_cnpj">Localização da Célula CNPJ:</label>
                        <input type="text" id="localizacao_cnpj" name="localizacao_cnpj" 
                               value="<?php echo htmlspecialchars($_POST['localizacao_cnpj'] ?? 'U5'); ?>" 
                               required placeholder="Ex: U8">
                        <small>Localização da célula no arquivo CSV que contém o CNPJ (ex: U5)</small>
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