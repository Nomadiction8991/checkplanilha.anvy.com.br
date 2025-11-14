<?php
require_once PROJECT_ROOT . '/auth.php'; // Autenticação

// Configurações da página
$pageTitle = 'Importar Planilha';
$backUrl = '../../../index.php';

// Iniciar buffer
ob_start();
?>

<form action="../../../CRUD/CREATE/importar-planilha.php" method="POST" enctype="multipart/form-data">
    <!-- Dados do Comum -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-geo-alt me-2"></i>
            Dados do Comum
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="administracao" class="form-label">Administração <span class="text-danger">*</span></label>
                    <select id="administracao" name="administracao" class="form-select" required>
                        <option value="">Carregando cidades de MT...</option>
                    </select>
                    <small class="text-muted">Formato: Cidade (MT)</small>
                </div>
                <div class="col-md-6">
                    <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                    <select id="cidade" name="cidade" class="form-select" required>
                        <option value="">Carregando cidades de MT...</option>
                    </select>
                    <small class="text-muted">Formato: Cidade (MT)</small>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <label for="setor" class="form-label">Setor (opcional)</label>
                    <input type="number" class="form-control" id="setor" name="setor" min="0" step="1" placeholder="Ex: 3">
                </div>
            </div>
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
                    <label for="mapeamento_complemento" class="form-label">Complemento <span class="text-danger">*</span></label>
                    <input type="text" class="form-control text-center fw-bold" id="mapeamento_complemento" name="mapeamento_complemento" value="D" maxlength="2" required>
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
$contentFile = PROJECT_ROOT . '/temp_importar_planilha_content_' . uniqid() . '.php';
file_put_contents($contentFile, $contentHtml);
include PROJECT_ROOT . '/layouts/app-wrapper.php';
@unlink($contentFile);
?>
<script>
// Popula os selects de Administração e Cidade com as cidades do MT (independentes)
document.addEventListener('DOMContentLoaded', async function(){
    const selAdm = document.getElementById('administracao');
    const selCid = document.getElementById('cidade');

    async function carregarCidadesMT(){
        try {
            // Buscar lista de estados, localizar MT e carregar municípios
            const respUF = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados');
            const estados = await respUF.json();
            const mt = estados.find(e => e.sigla === 'MT');
            if (!mt) throw new Error('UF MT não encontrada');

            const respCidades = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/' + mt.id + '/municipios');
            let cidades = await respCidades.json();
            cidades = cidades.map(c => c.nome).sort((a,b)=>a.localeCompare(b));

            const optionsHtml = ['<option value="">Selecione...</option>']
                .concat(cidades.map(nome => {
                    const label = `${nome} (MT)`;
                    return `<option value="${label}">${label}</option>`;
                }))
                .join('');

            selAdm.innerHTML = optionsHtml;
            selCid.innerHTML = optionsHtml;
        } catch (e) {
            selAdm.innerHTML = '<option value="">Erro ao carregar</option>';
            selCid.innerHTML = '<option value="">Erro ao carregar</option>';
            console.error(e);
        }
    }

    carregarCidadesMT();
});
</script>
