<?php
require_once __DIR__ . '/../../../CRUD/UPDATE/editar-planilha.php';

$pageTitle = "Editar Planilha";
$backUrl = '../../../index.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- Info Atual -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-info-circle me-2"></i>
            Informações Atuais
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">CNPJ</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['cnpj'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Comum</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['comum'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Endereço</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['endereco'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data Posição</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['data_posicao'] ?? ''); ?>" disabled>
                </div>
            </div>
            
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo ($planilha['ativo'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ativo">
                    Planilha Ativa
                </label>
            </div>
        </div>
    </div>

    <!-- Configurações -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            Configurações de Importação
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="linhas_pular" class="form-label">Linhas Iniciais a Pular</label>
                <input type="number" class="form-control" id="linhas_pular" name="linhas_pular" 
                       value="<?php echo $config['pulo_linhas'] ?? 25; ?>" min="0" required>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="localizacao_cnpj" class="form-label">CNPJ</label>
                    <input type="text" class="form-control" id="localizacao_cnpj" name="localizacao_cnpj" 
                           value="<?php echo htmlspecialchars($config['cnpj'] ?? 'U5'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_comum" class="form-label">Comum</label>
                    <input type="text" class="form-control" id="localizacao_comum" name="localizacao_comum" 
                           value="<?php echo htmlspecialchars($config['comum'] ?? 'D16'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_endereco" class="form-label">Endereço</label>
                    <input type="text" class="form-control" id="localizacao_endereco" name="localizacao_endereco" 
                           value="<?php echo htmlspecialchars($config['endereco'] ?? 'A4'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_data_posicao" class="form-label">Data Posição</label>
                    <input type="text" class="form-control" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                           value="<?php echo htmlspecialchars($config['data_posicao'] ?? 'D13'); ?>" required>
                </div>
            </div>

            <h6 class="mt-4 mb-3">Mapeamento de Colunas</h6>
            <div class="row g-3">
                <div class="col-4">
                    <label for="codigo" class="form-label">Código</label>
                    <input type="text" class="form-control text-center fw-bold" name="codigo" 
                           value="<?php echo $mapeamento_array['codigo'] ?? 'A'; ?>" maxlength="3" required>
                </div>
                <div class="col-4">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control text-center fw-bold" name="nome" 
                           value="<?php echo $mapeamento_array['nome'] ?? 'D'; ?>" maxlength="3" required>
                </div>
                <div class="col-4">
                    <label for="dependencia" class="form-label">Dependência</label>
                    <input type="text" class="form-control text-center fw-bold" name="dependencia" 
                           value="<?php echo $mapeamento_array['dependencia'] ?? 'P'; ?>" maxlength="3" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Atualizar Dados -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-arrow-repeat me-2"></i>
            Atualizar Dados
        </div>
        <div class="card-body">
            <label for="arquivo" class="form-label">Novo Arquivo CSV (opcional)</label>
            <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".csv">
            <div class="form-text">Selecione apenas se desejar substituir os dados atuais</div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        Atualizar Planilha
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_editar_planilha_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
