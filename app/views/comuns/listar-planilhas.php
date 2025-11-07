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

// ===== Consulta de planilhas (aplicando filtros) =====
$fs = $_GET['filtro_status'] ?? 'todas';
$data_inicio_str = trim($_GET['data_inicio'] ?? '');
$data_fim_str = trim($_GET['data_fim'] ?? '');

// Agora inputs vêm em formato yyyy-mm-dd direto do input type="date"
$data_inicio_mysql = $data_inicio_str !== '' ? $data_inicio_str : null;
$data_fim_mysql = $data_fim_str !== '' ? $data_fim_str : null;

$planilhas = [];
$total_registros = 0;

try {
    $conds = ['p.comum_id = :comum_id'];
    $params = [':comum_id' => (int)$comum_id];

    // Filtro por intervalo de datas
    if ($data_inicio_mysql && $data_fim_mysql) {
        $conds[] = 'p.data_posicao BETWEEN :data_inicio AND :data_fim';
        $params[':data_inicio'] = $data_inicio_mysql;
        $params[':data_fim'] = $data_fim_mysql;
    } elseif ($data_inicio_mysql) {
        $conds[] = 'p.data_posicao >= :data_inicio';
        $params[':data_inicio'] = $data_inicio_mysql;
    } elseif ($data_fim_mysql) {
        $conds[] = 'p.data_posicao <= :data_fim';
        $params[':data_fim'] = $data_fim_mysql;
    }

    if ($fs === 'ativas') {
        $conds[] = 'p.ativo = 1';
    } elseif ($fs === 'inativas') {
        $conds[] = 'p.ativo = 0';
    }

    $where = implode(' AND ', $conds);
    $sql = "SELECT p.id, p.comum_id, p.data_posicao, p.ativo
        FROM planilhas p
        WHERE $where
        ORDER BY p.data_posicao DESC, p.id DESC";
    $stmt = $conexao->prepare($sql);
    foreach ($params as $k=>$v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $planilhas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_registros = is_array($planilhas) ? count($planilhas) : 0;
} catch (Exception $e) {
    // Exibiremos o erro mais abaixo no bloco de listagem
    $erro_carregar = $e->getMessage();
}

ob_start();
?>

<!-- Card de Filtros (estilo similar à view da planilha) -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>
        Filtros
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <input type="hidden" name="comum_id" value="<?php echo (int)$comum_id; ?>">

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label" for="data_inicio">
                        <i class="bi bi-calendar-date me-1"></i>
                        Data inicial
                    </label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio"
                           value="<?php echo $data_inicio_mysql ?? ''; ?>">
                </div>
                <div class="col-6">
                    <label class="form-label" for="data_fim">
                        <i class="bi bi-calendar-date me-1"></i>
                        Data final
                    </label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim"
                           value="<?php echo $data_fim_mysql ?? ''; ?>">
                </div>
            </div>

            <div class="accordion" id="filtrosAvancados">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros">
                            <i class="bi bi-sliders me-2"></i>
                            Filtros Avançados
                        </button>
                    </h2>
                    <div id="collapseFiltros" class="accordion-collapse collapse" data-bs-parent="#filtrosAvancados">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label class="form-label" for="filtro_status">Status</label>
                                <select id="filtro_status" name="filtro_status" class="form-select">
                                    <option value="todas" <?php echo $fs==='todas'?'selected':''; ?>>Todas</option>
                                    <option value="ativas" <?php echo $fs==='ativas'?'selected':''; ?>>Ativas</option>
                                    <option value="inativas" <?php echo $fs==='inativas'?'selected':''; ?>>Inativas</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-grid mt-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>
                    Filtrar
                </button>
            </div>
        </form>
    </div>
    <div class="card-footer text-muted small">
        <?php echo (int)$total_registros; ?> planilha(s) encontrada(s)
    </div>
</div>

<!-- Card de Listagem -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-list me-2"></i>
            Planilhas
        </span>
        <span class="badge bg-white text-dark"><?php echo (int)$total_registros; ?> itens</span>
    </div>
    <div class="card-body p-0">
        <?php if (!empty($erro_carregar)): ?>
            <div class="alert alert-danger m-3">Erro ao carregar planilhas: <?php echo htmlspecialchars($erro_carregar); ?></div>
        <?php else: ?>
            <?php if (empty($planilhas)): ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Nenhuma planilha cadastrada para este comum
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0 align-middle text-center">
                        <thead>
                            <tr>
                                <th style="width: 80px">#</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th style="width: 120px">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($planilhas as $planilha): ?>
                                <?php
                                    $status_badge = $planilha['ativo'] ? 'bg-success' : 'bg-secondary';
                                    $status_texto = $planilha['ativo'] ? 'Ativa' : 'Inativa';
                                    
                                    // Formatar data para dd/mm/yyyy independente do formato de origem
                                    $data_formatada = '-';
                                    if (!empty($planilha['data_posicao'])) {
                                        $rawData = trim($planilha['data_posicao']);
                                        $dt = null;
                                        // Tentar formatos comuns
                                        $formatos = ['Y-m-d','d/m/Y','m/d/Y','Y-m-d H:i:s','d/m/Y H:i:s','m/d/Y H:i:s'];
                                        foreach ($formatos as $f) {
                                            $dt = DateTime::createFromFormat($f, $rawData);
                                            if ($dt) { break; }
                                        }
                                        // Como fallback usar strtotime
                                        if (!$dt) {
                                            $ts = strtotime($rawData);
                                            if ($ts) { $dt = (new DateTime())->setTimestamp($ts); }
                                        }
                                        if ($dt) {
                                            $data_formatada = $dt->format('d/m/Y');
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><strong><?php echo $planilha['id']; ?></strong></td>
                                    <td><?php echo $data_formatada; ?></td>
                                    <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_texto; ?></span></td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="../planilhas/view-planilha.php?id=<?php echo $planilha['id']; ?>" class="btn btn-sm btn-primary" title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="../planilhas/editar-planilha.php?id=<?php echo $planilha['id']; ?>" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
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

