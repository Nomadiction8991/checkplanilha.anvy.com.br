<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/conexao.php';
require_once __DIR__ . '/../../../app/functions/comum_functions.php';

// Configurações da página
$pageTitle = 'Importar Planilha';
$backUrl = '../../../index.php';

// Carregar comuns para seleção
$comuns = obter_todos_comuns($conexao);

// Iniciar buffer
ob_start();
?>

<form action="../../../CRUD/CREATE/importar-planilha.php" method="POST" enctype="multipart/form-data">
    <!-- Selecionar Comum -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-building me-2"></i>
            Selecionar Comum
        </div>
        <div class="card-body">
            <label for="comum_id" class="form-label">Comum <span class="text-danger">*</span></label>
            <select id="comum_id" name="comum_id" class="form-select" required>
                <option value="">Selecione…</option>
                <?php foreach ($comuns as $c): ?>
                    <option value="<?php echo (int)$c['id']; ?>">
                        <?php echo htmlspecialchars('BR ' . substr(str_pad($c['codigo'], 6, '0', STR_PAD_LEFT), 0, 2) . '-' . substr(str_pad($c['codigo'], 6, '0', STR_PAD_LEFT), 2) . ' - ' . $c['descricao']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Arquivo CSV -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>
            Arquivo CSV
        </div>
        <div class="card-body">
            <label for="arquivo_csv" class="form-label">Arquivo CSV <span class="text-danger">*</span></label>
            <input type="file" class="form-control" id="arquivo_csv" name="arquivo_csv" accept=".csv" required>
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
                <label for="pulo_linhas" class="form-label">Linhas iniciais a pular <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="pulo_linhas" name="pulo_linhas" value="25" min="0" required>
            </div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="posicao_comum" class="form-label">Célula Comum <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="posicao_comum" name="posicao_comum" value="D16" required>
                </div>
                <div class="col-md-4">
                    <label for="posicao_data" class="form-label">Célula Data <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="posicao_data" name="posicao_data" value="D13" required>
                </div>
                <div class="col-md-4">
                    <label for="posicao_cnpj" class="form-label">Célula CNPJ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="posicao_cnpj" name="posicao_cnpj" value="U5" required>
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
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="mapeamento_codigo" class="form-label">Código <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center fw-bold" id="mapeamento_codigo" name="mapeamento_codigo" value="A" maxlength="2" required>
                </div>
                <div class="col-md-4">
                    <label for="mapeamento_nome" class="form-label">Nome <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center fw-bold" id="mapeamento_nome" name="mapeamento_nome" value="D" maxlength="2" required>
                </div>
                <div class="col-md-4">
                    <label for="mapeamento_dependencia" class="form-label">Dependência <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center fw-bold" id="mapeamento_dependencia" name="mapeamento_dependencia" value="P" maxlength="2" required>
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
$contentFile = __DIR__ . '/../../../temp_importar_planilha_content_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include __DIR__ . '/../layouts/app-wrapper.php';
@unlink($contentFile);
?>
