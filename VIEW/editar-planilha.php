<?php
require_once '../CRUD/UPDATE/editar-planilha.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Planilha - Anvy</title>
    <link rel="stylesheet" href="../STYLE/editar-planilha.css">
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
            <h1>Editar Planilha</h1>
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
                <div class="card">
                    <h2>Informações Atuais da Planilha</h2>
                    <div class="form-group">
                        <label>CNPJ:</label>
                        <input type="text" value="<?php echo htmlspecialchars($planilha['cnpj'] ?? ''); ?>" disabled style="background: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>Comum:</label>
                        <input type="text" value="<?php echo htmlspecialchars($planilha['comum'] ?? ''); ?>" disabled style="background: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>Endereço:</label>
                        <input type="text" value="<?php echo htmlspecialchars($planilha['endereco'] ?? ''); ?>" disabled style="background: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <label>Data Posição:</label>
                        <input type="text" value="<?php echo htmlspecialchars($planilha['data_posicao'] ?? ''); ?>" disabled style="background: #f8f9fa;">
                    </div>
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="ativo" name="ativo" value="1" 
                                   <?php echo ($planilha['ativo'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="ativo">Planilha Ativa</label>
                        </div>
                        <small>Desmarque para desativar esta planilha</small>
                    </div>
                </div>

                <div class="card">
                    <h2>Configurações de Importação</h2>
                    <div class="form-group">
                        <label for="localizacao_cnpj">CNPJ:</label>
                        <input type="text" id="localizacao_cnpj" name="localizacao_cnpj" 
                               value="<?php echo htmlspecialchars($config['cnpj'] ?? 'U5'); ?>" 
                               required placeholder="Ex: U8">
                        <small>Localização da célula no arquivo CSV que contém o CNPJ (ex: U5)</small>
                    </div>
                    <div class="form-group">
                        <label for="localizacao_comum">Comum:</label>
                        <input type="text" id="localizacao_comum" name="localizacao_comum" 
                               value="<?php echo htmlspecialchars($config['comum'] ?? 'D16'); ?>" 
                               required placeholder="Ex: D16">
                        <small>Localização da célula no arquivo CSV que contém o valor comum (ex: D16)</small>
                    </div>
                    <div class="form-group">
                        <label for="localizacao_endereco">Endereço:</label>
                        <input type="text" id="localizacao_endereco" name="localizacao_endereco" 
                               value="<?php echo htmlspecialchars($config['endereco'] ?? 'A4'); ?>" 
                               required placeholder="Ex: A4">
                        <small>Localização da célula no arquivo CSV que contém o endereço (ex: A4)</small>
                    </div>
                    <div class="form-group">
                        <label for="localizacao_data_posicao">Data Posição:</label>
                        <input type="text" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                               value="<?php echo htmlspecialchars($config['data_posicao'] ?? 'D13'); ?>" 
                               required placeholder="Ex: D13">
                        <small>Localização da célula no arquivo CSV que contém a data de posição (ex: D13)</small>
                    </div>

                    <h3 style="margin: 20px 0 10px 0; font-size: 14px; color: #495057;">Mapeamento de Colunas</h3>
                    <p style="margin-bottom: 15px; font-size: 13px; color: #666;">Defina a letra da coluna para cada campo:</p>

                    <div class="form-group">
                        <label for="linhas_pular">Linhas Iniciais a Pular:</label>
                        <input type="number" id="linhas_pular" name="linhas_pular" 
                               value="<?php echo $config['pulo_linhas'] ?? 25; ?>" min="0" required>
                        <small>Número de linhas do cabeçalho que devem ser ignoradas</small>
                    </div>
                    <div class="mapeamento-grid">
                        <div class="mapeamento-item">
                            <span class="mapeamento-label">Código:</span>
                            <input type="text" class="mapeamento-input" name="codigo" 
                                   value="<?php echo $mapeamento_array['codigo'] ?? 'A'; ?>" maxlength="2" required>
                        </div>

                        <div class="mapeamento-item">
                            <span class="mapeamento-label">Nome:</span>
                            <input type="text" class="mapeamento-input" name="nome" 
                                   value="<?php echo $mapeamento_array['nome'] ?? 'D'; ?>" maxlength="2" required>
                        </div>

                        <div class="mapeamento-item">
                            <span class="mapeamento-label">Dependência:</span>
                            <input type="text" class="mapeamento-input" name="dependencia" 
                                   value="<?php echo $mapeamento_array['dependencia'] ?? 'P'; ?>" maxlength="2" required>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Atualizar Dados</h2>
                    
                    <div class="form-group">
                        <label for="arquivo">Novo Arquivo CSV (opcional):</label>
                        <input type="file" id="arquivo" name="arquivo" accept=".csv">
                        <small>Selecione um novo arquivo apenas se desejar substituir os dados atuais</small>
                    </div>
                </div>

                <div class="botoes">
                    <button type="submit" class="btn btn-primary">Atualizar Planilha</button>
                </div>
            </form>
        </div>
    </section>
</body>
</html>