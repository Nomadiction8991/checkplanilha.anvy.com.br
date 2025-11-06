<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../CRUD/conexao.php';
require_once __DIR__ . '/../../../app/functions/comum_functions.php';

$comum_id = $_GET['comum_id'] ?? null;

if (!$comum_id) {
    header('Location: ../../../index.php');
    exit;
}

$comum = obter_comum_por_id($conexao, $comum_id);
if (!$comum) {
    header('Location: ../../../index.php');
    exit;
}

$pageTitle = "Anvy - Planilhas - " . $comum['descricao'];
$backUrl = "../../../index.php";
$headerActions = '
    <a href="menu.php?comum_id=' . $comum_id . '" class="btn-header-action" title="Menu">
        <i class="bi bi-list fs-5"></i>
    </a>
    <a href="../../../logout.php" class="btn-header-action" title="Sair" onclick="return confirm(\'Deseja realmente sair?\')">
        <i class="bi bi-box-arrow-right fs-5"></i>
    </a>
';

ob_start();
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-info text-white">
                <div class="card-body">
                    <h2 class="card-title mb-0">
                        <i class="bi bi-building me-2"></i>
                        <?php echo htmlspecialchars($comum['descricao']); ?>
                    </h2>
                    <small class="text-white-50">
                        <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($comum['cidade']); ?>
                        <?php if (!empty($comum['cnpj'])) echo ' • CNPJ: ' . htmlspecialchars($comum['cnpj']); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Abas -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="planilhas-tab" data-bs-toggle="tab" data-bs-target="#planilhas-content" type="button" role="tab">
                <i class="bi bi-file-earmark me-2"></i>Planilhas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="importar-tab" data-bs-toggle="tab" data-bs-target="#importar-content" type="button" role="tab">
                <i class="bi bi-upload me-2"></i>Importar Planilha
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Aba: Planilhas -->
        <div class="tab-pane fade show active" id="planilhas-content" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-list me-2"></i>Planilhas Cadastradas
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            try {
                                $planilhas = obter_planilhas_por_comum($conexao, $comum_id);
                                
                                if (empty($planilhas)) {
                                    echo '<div class="alert alert-info text-center py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            <p>Nenhuma planilha cadastrada para este comum</p>
                                          </div>';
                                } else {
                                    echo '<div class="table-responsive">';
                                    echo '<table class="table table-hover mb-0">';
                                    echo '<thead class="table-light">';
                                    echo '<tr>';
                                    echo '<th>#</th>';
                                    echo '<th>Data</th>';
                                    echo '<th>Produtos</th>';
                                    echo '<th>Status</th>';
                                    echo '<th>Ações</th>';
                                    echo '</tr>';
                                    echo '</thead>';
                                    echo '<tbody>';
                                    
                                    foreach ($planilhas as $planilha) {
                                        $status_badge = $planilha['ativo'] ? 'bg-success' : 'bg-secondary';
                                        $status_texto = $planilha['ativo'] ? 'Ativa' : 'Inativa';
                                        
                                        echo '<tr>';
                                        echo '<td><strong>' . $planilha['id'] . '</strong></td>';
                                        echo '<td>' . ($planilha['data_posicao'] ? date('d/m/Y', strtotime($planilha['data_posicao'])) : '-') . '</td>';
                                        echo '<td><span class="badge bg-info">' . $planilha['total_produtos'] . '</span></td>';
                                        echo '<td><span class="badge ' . $status_badge . '">' . $status_texto . '</span></td>';
                                        echo '<td>';
                                        echo '<a href="../../../CRUD/READ/view-planilha.php?id=' . $planilha['id'] . '" class="btn btn-sm btn-primary" title="Visualizar">';
                                        echo '<i class="bi bi-eye"></i></a> ';
                                        echo '<a href="../../../CRUD/UPDATE/editar-planilha.php?id=' . $planilha['id'] . '" class="btn btn-sm btn-warning" title="Editar">';
                                        echo '<i class="bi bi-pencil"></i></a>';
                                        echo '</td>';
                                        echo '</tr>';
                                    }
                                    
                                    echo '</tbody>';
                                    echo '</table>';
                                    echo '</div>';
                                }
                            } catch (Exception $e) {
                                echo '<div class="alert alert-danger">Erro ao carregar planilhas: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aba: Importar Planilha -->
        <div class="tab-pane fade" id="importar-content" role="tabpanel">
            <div class="row">
                <div class="col-lg-8 offset-lg-2">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-upload me-2"></i>Importar Nova Planilha
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="../../../CRUD/CREATE/importar-planilha.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="comum_id" value="<?php echo $comum_id; ?>">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="administracao" class="form-label">Administração <span class="text-danger">*</span></label>
                                        <select id="administracao" name="administracao" class="form-select" required>
                                            <option value="">Carregando cidades de MT...</option>
                                        </select>
                                        <small class="text-muted">Formato: Cidade (MT)</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                                        <select id="cidade" name="cidade" class="form-select" required>
                                            <option value="">Carregando cidades de MT...</option>
                                        </select>
                                        <small class="text-muted">Formato: Cidade (MT)</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="setor" class="form-label">Setor (opcional)</label>
                                        <input type="number" class="form-control" id="setor" name="setor" min="0" step="1" placeholder="Ex: 3">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="arquivo_csv" class="form-label">Arquivo CSV</label>
                                    <input type="file" class="form-control" id="arquivo_csv" name="arquivo_csv" accept=".csv" required>
                                    <small class="form-text text-muted">Selecione um arquivo CSV para importar</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="posicao_comum" class="form-label">Célula: Comum</label>
                                        <input type="text" class="form-control" id="posicao_comum" name="posicao_comum" value="D16" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="posicao_data" class="form-label">Célula: Data</label>
                                        <input type="text" class="form-control" id="posicao_data" name="posicao_data" value="D13" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="posicao_cnpj" class="form-label">Célula: CNPJ</label>
                                        <input type="text" class="form-control" id="posicao_cnpj" name="posicao_cnpj" value="U5" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="pulo_linhas" class="form-label">Pular Linhas</label>
                                        <input type="number" class="form-control" id="pulo_linhas" name="pulo_linhas" value="25" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Mapeamento de Colunas</label>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" id="mapeamento_codigo" name="mapeamento_codigo" placeholder="Coluna: Código" value="A">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" id="mapeamento_complemento" name="mapeamento_complemento" placeholder="Coluna: Complemento" value="D">
                                        </div>
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" id="mapeamento_dependencia" name="mapeamento_dependencia" placeholder="Coluna: Dependência" value="P">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-circle me-2"></i>Importar Planilha
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$contentHtml = ob_get_clean();
$contentFile = __DIR__ . '/../../../temp_content.php';
file_put_contents($contentFile, $contentHtml);

// Incluir layout app-wrapper (padronizado)
require_once __DIR__ . '/../layouts/app-wrapper.php';

// Limpar arquivo temporário
@unlink($contentFile);
?>
<script>
// Popula Administração e Cidade com as cidades do MT (independentes)
document.addEventListener('DOMContentLoaded', async function(){
    const selAdm = document.getElementById('administracao');
    const selCid = document.getElementById('cidade');

    async function carregarCidadesMT(){
        try {
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

            if (selAdm) selAdm.innerHTML = optionsHtml;
            if (selCid) selCid.innerHTML = optionsHtml;
        } catch (e) {
            if (selAdm) selAdm.innerHTML = '<option value="">Erro ao carregar</option>';
            if (selCid) selCid.innerHTML = '<option value="">Erro ao carregar</option>';
            console.error(e);
        }
    }

    carregarCidadesMT();
});
</script>
