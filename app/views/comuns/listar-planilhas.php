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
        <button class="btn-header-action" type="button" id="menuPlanilhas" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuPlanilhas">
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
        <div class="d-flex flex-column gap-2">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-list me-2"></i>
                <strong>Planilhas</strong>
            </div>
            <form method="GET" class="row g-2">
                <input type="hidden" name="comum_id" value="<?php echo (int)$comum_id; ?>">
                <div class="col-8">
                    <label for="busca_data" class="form-label mb-1">Data</label>
                    <input type="date" class="form-control" id="busca_data" name="busca_data" value="<?php echo htmlspecialchars($_GET['busca_data'] ?? ''); ?>">
                </div>
                <div class="col-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-light w-100"><i class="bi bi-search me-1"></i>Buscar</button>
                </div>
                <div class="col-12">
                    <a class="small" data-bs-toggle="collapse" href="#filtrosAvancados" role="button" aria-expanded="false" aria-controls="filtrosAvancados">
                        <i class="bi bi-sliders me-1"></i>Filtros avançados
                    </a>
                    <div class="collapse mt-2" id="filtrosAvancados">
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="filtro_status" class="form-label mb-1">Status</label>
                                <select id="filtro_status" name="filtro_status" class="form-select">
                                    <?php $fs = $_GET['filtro_status'] ?? 'todas'; ?>
                                    <option value="todas" <?php echo $fs==='todas'?'selected':''; ?>>Todas</option>
                                    <option value="ativas" <?php echo $fs==='ativas'?'selected':''; ?>>Ativas</option>
                                    <option value="inativas" <?php echo $fs==='inativas'?'selected':''; ?>>Inativas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <?php
        try {
            // Montar filtro dinâmico
            $conds = ['p.comum_id = :comum_id'];
            $params = [':comum_id' => (int)$comum_id];
            if (!empty($_GET['busca_data'])) {
                $d = $_GET['busca_data'];
                // aceitar YYYY-MM-DD diretamente
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) {
                    $conds[] = 'p.data_posicao = :data_posicao';
                    $params[':data_posicao'] = $d;
                }
            }
            $fs = $_GET['filtro_status'] ?? 'todas';
            if ($fs === 'ativas') {
                $conds[] = 'p.ativo = 1';
            } elseif ($fs === 'inativas') {
                $conds[] = 'p.ativo = 0';
            }

            $where = implode(' AND ', $conds);
            $sql = "SELECT p.id, p.comum_id, p.data_posicao, p.ativo, COUNT(pr.id_produto) as total_produtos
                    FROM planilhas p
                    LEFT JOIN produtos pr ON p.id = pr.planilha_id
                    WHERE $where
                    GROUP BY p.id, p.comum_id, p.data_posicao, p.ativo
                    ORDER BY p.data_posicao DESC, p.id DESC";
            $stmt = $conexao->prepare($sql);
            foreach ($params as $k=>$v) {
                $stmt->bindValue($k, $v);
            }
            $stmt->execute();
            $planilhas = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
