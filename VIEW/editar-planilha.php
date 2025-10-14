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
                <!-- Informações Básicas -->
                <div class="card">
                    <h2>Informações da Planilha</h2>
                    
                    <!-- Valor Atual Comum -->
                    <div class="form-group">
                        <label>Valor Comum Atual:</label>
                        <input type="text" value="<?php echo htmlspecialchars($planilha['comum'] ?? ''); ?>" readonly style="background: #f8f9fa;">
                        <small>Valor obtido da célula comum do arquivo CSV</small>
                    </div>

                    <!-- Valor Atual Data Posição -->
                    <div class="form-group">
                        <label>Data Posição Atual:</label>
                        <input type="text" value="<?php echo htmlspecialchars($planilha['data_posicao'] ?? ''); ?>" readonly style="background: #f8f9fa;">
                        <small>Data obtida da célula data_posicao do arquivo CSV</small>
                    </div>
                    
                    <!-- Campo Ativo -->
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="ativo" name="ativo" value="1" 
                                   <?php echo ($planilha['ativo'] ?? 0) ? 'checked' : ''; ?>>
                            <label for="ativo">Planilha ativa</label>
                        </div>
                        <small>Desmarque para desativar esta planilha</small>
                    </div>
                </div>

                <!-- Informações de Endereço e CNPJ -->
                <div class="card">
                    <h2>Informações de Endereço e CNPJ</h2>
                    
                    <!-- Valor Atual Endereço -->
                    <div class="form-group">
                        <label>Endereço Atual:</label>
                        <input type="text" value="<?php echo htmlspecialchars($planilha['endereco'] ?? ''); ?>" readonly style="background: #f8f9fa;">
                        <small>Endereço obtido da célula endereco do arquivo CSV</small>
                    </div>

                    <!-- Valor Atual CNPJ -->
                    <div class="form-group">
                        <label>CNPJ Atual:</label>
                        <input type="text" value="<?php echo htmlspecialchars($planilha['cnpj'] ?? ''); ?>" readonly style="background: #f8f9fa;">
                        <small>CNPJ obtido da célula cnpj do arquivo CSV (apenas números)</small>
                    </div>
                </div>

                <!-- Configurações de Importação -->
                <div class="card">
                    <h2>Configurações de Importação</h2>
                    
                    <!-- Linhas a pular -->
                    <div class="form-group">
                        <label for="linhas_pular">Linhas iniciais a pular:</label>
                        <input type="number" id="linhas_pular" name="linhas_pular" 
                               value="<?php echo $config['pulo_linhas'] ?? 25; ?>" min="0" required>
                        <small>Número de linhas do cabeçalho que devem ser ignoradas</small>
                    </div>

                    <!-- Campo Localização Comum -->
                    <div class="form-group">
                        <label for="localizacao_comum">Localização da Célula Comum:</label>
                        <input type="text" id="localizacao_comum" name="localizacao_comum" 
                               value="<?php echo htmlspecialchars($config['comum'] ?? 'D16'); ?>" 
                               required placeholder="Ex: D16">
                        <small>Localização da célula no arquivo CSV que contém o valor comum (ex: D16)</small>
                    </div>

                    <!-- Campo Localização Data Posição -->
                    <div class="form-group">
                        <label for="localizacao_data_posicao">Localização da Célula Data Posição:</label>
                        <input type="text" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                               value="<?php echo htmlspecialchars($config['data_posicao'] ?? 'D13'); ?>" 
                               required placeholder="Ex: D13">
                        <small>Localização da célula no arquivo CSV que contém a data de posição (ex: D13)</small>
                    </div>

                    <!-- Configurações de Endereço e CNPJ -->
                    <h3 style="margin: 20px 0 10px 0; font-size: 14px; color: #495057;">Configurações de Endereço e CNPJ</h3>

                    <!-- Campo Localização Endereço -->
                    <div class="form-group">
                        <label for="localizacao_endereco">Localização da Célula Endereço:</label>
                        <input type="text" id="localizacao_endereco" name="localizacao_endereco" 
                               value="<?php echo htmlspecialchars($config['endereco'] ?? 'A4'); ?>" 
                               required placeholder="Ex: A4">
                        <small>Localização da célula no arquivo CSV que contém o endereço (ex: A4)</small>
                    </div>

                    <!-- Campo Localização CNPJ -->
                    <div class="form-group">
                        <label for="localizacao_cnpj">Localização da Célula CNPJ:</label>
                        <input type="text" id="localizacao_cnpj" name="localizacao_cnpj" 
                               value="<?php echo htmlspecialchars($config['cnpj'] ?? 'U8'); ?>" 
                               required placeholder="Ex: U8">
                        <small>Localização da célula no arquivo CSV que contém o CNPJ (ex: U8)</small>
                    </div>

                    <!-- Mapeamento de Colunas -->
                    <h3 style="margin: 20px 0 10px 0; font-size: 14px; color: #495057;">Mapeamento de Colunas</h3>
                    <p style="margin-bottom: 15px; font-size: 13px; color: #666;">Defina a letra da coluna para cada campo:</p>

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

                <!-- Arquivo CSV (Opcional) -->
                <div class="card">
                    <h2>Atualizar Dados</h2>
                    
                    <div class="form-group">
                        <label for="arquivo">Novo Arquivo CSV (opcional):</label>
                        <input type="file" id="arquivo" name="arquivo" accept=".csv">
                        <small>Selecione um novo arquivo apenas se desejar substituir os dados atuais</small>
                    </div>
                </div>

                <div class="botoes">
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                            <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                        </svg>
                        Atualizar Planilha
                    </button>
                </div>
            </form>
        </div>
    </section>
</body>
</html>