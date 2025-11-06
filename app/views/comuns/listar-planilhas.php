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

$pageTitle = $comum['descricao'];
$backUrl = "../../../index.php";
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuPrincipal" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuPrincipal">
            <li>
                <a class="dropdown-item" href="../planilhas/importar-planilha.php">
                    <i class="bi bi-upload me-2"></i>Importar Planilha
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="../../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

ob_start();
?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list me-2"></i>Planilhas
    </div>
    <div class="card-body p-0">
        <?php
        try {
            $planilhas = obter_planilhas_por_comum($conexao, $comum_id);

            if (empty($planilhas)) {
                echo '<div class="p-4 text-center text-muted">'
                    . '<i class="bi bi-inbox fs-1 d-block mb-2"></i>'
                    . 'Nenhuma planilha cadastrada para este comum'
                    . '</div>';
            } else {
                echo '<div class="table-responsive">';
                echo '<table class="table table-hover table-striped mb-0 align-middle">';
                echo '<thead>';
                echo '<tr>';
                echo '<th style="width: 80px">#</th>';
                echo '<th>Data</th>';
                echo '<th>Produtos</th>';
                echo '<th>Status</th>';
                echo '<th style="width: 120px">Ações</th>';
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
            echo '<div class="alert alert-danger m-3">Erro ao carregar planilhas: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
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
