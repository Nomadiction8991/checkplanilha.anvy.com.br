<?php
require_once __DIR__ . '/../../../CRUD/CREATE/importar-planilha.php';

// Configurações da página
$pageTitle = "Importar Planilha";
$backUrl = '../../../index.php';

// Iniciar buffer
ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- Arquivo CSV -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>
            Arquivo
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="arquivo" class="form-label">Arquivo CSV *</label>
                <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".csv" required>
                <div class="form-text">Selecione o arquivo CSV para importação</div>
            </div>
        </div>
    </div>

    <!-- Configurações Básicas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            Configurações Básicas
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="linhas_pular" class="form-label">Linhas iniciais a pular</label>
                <input type="number" class="form-control" id="linhas_pular" name="linhas_pular" 
                       value="<?php echo $_POST['linhas_pular'] ?? 25; ?>" min="0" required>
                <div class="form-text">Número de linhas do cabeçalho que devem ser ignoradas</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="localizacao_comum" class="form-label">Célula Comum</label>
                    <input type="text" class="form-control" id="localizacao_comum" name="localizacao_comum" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_comum'] ?? 'D16'); ?>" 
                           required placeholder="Ex: D16">
                    <div class="form-text">Ex: D16</div>
                </div>

                <div class="col-md-6">
                    <label for="localizacao_data_posicao" class="form-label">Célula Data Posição</label>
                    <input type="text" class="form-control" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_data_posicao'] ?? 'D13'); ?>" 
                           required placeholder="Ex: D13">
                    <div class="form-text">Ex: D13</div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="localizacao_endereco" class="form-label">Célula Endereço</label>
                    <input type="text" class="form-control" id="localizacao_endereco" name="localizacao_endereco" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_endereco'] ?? 'A4'); ?>" 
                           required placeholder="Ex: A4">
                    <div class="form-text">Ex: A4</div>
                </div>

                <div class="col-md-6">
                    <label for="localizacao_cnpj" class="form-label">Célula CNPJ</label>
                    <input type="text" class="form-control" id="localizacao_cnpj" name="localizacao_cnpj" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_cnpj'] ?? 'U5'); ?>" 
                           required placeholder="Ex: U5">
                    <div class="form-text">Ex: U5</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapeamento de Colunas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-columns-gap me-2"></i>
            Mapeamento de Colunas
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Defina a letra da coluna para cada campo</p>
            
            <div class="row g-3">
                <div class="col-4">
                    <label for="codigo" class="form-label">Código</label>
                    <input type="text" class="form-control text-center fw-bold" name="codigo" 
                           value="<?php echo $_POST['codigo'] ?? 'A'; ?>" maxlength="2" required>
                </div>

                <div class="col-4">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control text-center fw-bold" name="nome" 
                           value="<?php echo $_POST['nome'] ?? 'D'; ?>" maxlength="2" required>
                </div>

                <div class="col-4">
                    <label for="dependencia" class="form-label">Dependência</label>
                    <input type="text" class="form-control text-center fw-bold" name="dependencia" 
                           value="<?php echo $_POST['dependencia'] ?? 'P'; ?>" maxlength="2" required>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-upload me-2"></i>
        Importar Planilha
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_importar_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
