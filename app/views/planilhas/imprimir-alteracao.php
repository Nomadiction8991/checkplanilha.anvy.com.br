<?php
// Agora: página integrada ao layout app-wrapper (Bootstrap 5, 400px)
require_once __DIR__ . '/../../../CRUD/conexao.php';

$id_planilha = $_GET['id'] ?? null;
if (!$id_planilha) { header('Location: ../../index.php'); exit; }

// Buscar dados da planilha
try {
    $sql_planilha = "SELECT * FROM planilhas WHERE id = :id";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':id', $id_planilha);
    $stmt_planilha->execute();
    $planilha = $stmt_planilha->fetch();
    if (!$planilha) { throw new Exception('Planilha não encontrada.'); }
} catch (Exception $e) { die("Erro ao carregar planilha: " . $e->getMessage()); }

$mostrar_pendentes = isset($_GET['mostrar_pendentes']);
$mostrar_checados = isset($_GET['mostrar_checados']);
$mostrar_observacao = isset($_GET['mostrar_observacao']);
$mostrar_checados_observacao = isset($_GET['mostrar_checados_observacao']);
$mostrar_dr = isset($_GET['mostrar_dr']);
$mostrar_etiqueta = isset($_GET['mostrar_etiqueta']);
$mostrar_alteracoes = isset($_GET['mostrar_alteracoes']);
$filtro_dependencia = $_GET['dependencia'] ?? '';

try {
    $sql_produtos = "SELECT p.*, pc.checado, pc.dr, pc.imprimir, pc.observacoes, pc.nome as nome_editado, pc.dependencia as dependencia_editada 
                     FROM produtos p 
                     LEFT JOIN produtos_check pc ON p.id = pc.produto_id 
                     WHERE p.id_planilha = :id_planilha";
    $params = [':id_planilha' => $id_planilha];
    if (!empty($filtro_dependencia)) { $sql_produtos .= " AND p.dependencia LIKE :dependencia"; $params[':dependencia'] = '%' . $filtro_dependencia . '%'; }
    $sql_produtos .= " ORDER BY p.codigo";
    $stmt_produtos = $conexao->prepare($sql_produtos);
    foreach ($params as $k => $v) { $stmt_produtos->bindValue($k, $v); }
    $stmt_produtos->execute();
    $todos_produtos = $stmt_produtos->fetchAll();
} catch (Exception $e) { die("Erro ao carregar produtos: " . $e->getMessage()); }

try {
    $sql_dependencias = "SELECT DISTINCT dependencia FROM produtos WHERE id_planilha = :id_planilha ORDER BY dependencia";
    $stmt_dependencias = $conexao->prepare($sql_dependencias);
    $stmt_dependencias->bindValue(':id_planilha', $id_planilha);
    $stmt_dependencias->execute();
    $dependencia_options = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $dependencia_options = []; }

$produtos_pendentes = $produtos_checados = $produtos_observacao = $produtos_checados_observacao = $produtos_dr = $produtos_etiqueta = $produtos_alteracoes = [];
foreach ($todos_produtos as $produto) {
    $tem_observacao = !empty($produto['observacoes']);
    $esta_checado = $produto['checado'] == 1;
    $esta_no_dr = $produto['dr'] == 1;
    $esta_etiqueta = $produto['imprimir'] == 1;
    $tem_alteracoes = false;
    if (!empty($produto['nome_editado']) && $produto['nome_editado'] != $produto['nome']) { $tem_alteracoes = true; }
    if (!empty($produto['dependencia_editada']) && $produto['dependencia_editada'] != $produto['dependencia']) { $tem_alteracoes = true; }
    if ($tem_alteracoes) $produtos_alteracoes[] = $produto;
    elseif ($esta_no_dr) $produtos_dr[] = $produto;
    elseif ($esta_etiqueta) $produtos_etiqueta[] = $produto;
    elseif ($tem_observacao && $esta_checado) $produtos_checados_observacao[] = $produto;
    elseif ($tem_observacao) $produtos_observacao[] = $produto;
    elseif ($esta_checado) $produtos_checados[] = $produto;
    else $produtos_pendentes[] = $produto;
}

$total_pendentes = count($produtos_pendentes);
$total_checados = count($produtos_checados);
$total_observacao = count($produtos_observacao);
$total_checados_observacao = count($produtos_checados_observacao);
$total_dr = count($produtos_dr);
$total_etiqueta = count($produtos_etiqueta);
$total_alteracoes = count($produtos_alteracoes);
$total_geral = count($todos_produtos);

$total_mostrar = 0;
if ($mostrar_pendentes) $total_mostrar += $total_pendentes;
if ($mostrar_checados) $total_mostrar += $total_checados;
if ($mostrar_observacao) $total_mostrar += $total_observacao;
if ($mostrar_checados_observacao) $total_mostrar += $total_checados_observacao;
if ($mostrar_dr) $total_mostrar += $total_dr;
if ($mostrar_etiqueta) $total_mostrar += $total_etiqueta;
if ($mostrar_alteracoes) $total_mostrar += $total_alteracoes;

// Cabeçalho do layout
$pageTitle = 'Imprimir Alterações';
$backUrl = '../shared/menu.php?id=' . $id_planilha;
$headerActions = '<button class="btn-header-action" title="Imprimir" onclick="window.print()"><i class="bi bi-printer"></i></button>';

// CSS de impressão e ajustes para o wrapper mobile
$customCss = '
@media print {
  .app-header, .no-print { display: none !important; }
  .app-container { padding: 0 !important; }
  .mobile-wrapper { max-width: 100% !important; border-radius: 0 !important; box-shadow: none !important; }
  .app-content { padding: 0 !important; background: #fff !important; }
  table { page-break-inside: auto; }
  tr { page-break-inside: avoid; page-break-after: auto; }
}
.table thead th { font-size: 12px; }
.table td { font-size: 12px; }
';

// Conteúdo da página
ob_start();
?>

<!-- Filtros -->
<div class="card mb-3 no-print">
  <div class="card-header">
    <i class="bi bi-filter-circle me-2"></i> Filtros do relatório
  </div>
  <div class="card-body">
    <form method="GET" class="row g-3">
      <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
      <div class="col-12">
        <label class="form-label">Seções a incluir</label>
        <div class="row g-2">
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secPend" name="mostrar_pendentes" value="1" <?php echo $mostrar_pendentes ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secPend">Produtos pendentes (<?php echo $total_pendentes; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secChec" name="mostrar_checados" value="1" <?php echo $mostrar_checados ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secChec">Produtos checados (<?php echo $total_checados; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secObs" name="mostrar_observacao" value="1" <?php echo $mostrar_observacao ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secObs">Produtos com observação (<?php echo $total_observacao; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secChecObs" name="mostrar_checados_observacao" value="1" <?php echo $mostrar_checados_observacao ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secChecObs">Checados + observação (<?php echo $total_checados_observacao; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secDR" name="mostrar_dr" value="1" <?php echo $mostrar_dr ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secDR">Produtos no DR (<?php echo $total_dr; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secEtiq" name="mostrar_etiqueta" value="1" <?php echo $mostrar_etiqueta ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secEtiq">Produtos com etiqueta (<?php echo $total_etiqueta; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secAlt" name="mostrar_alteracoes" value="1" <?php echo $mostrar_alteracoes ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secAlt">Produtos com alterações (<?php echo $total_alteracoes; ?>)</label>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12">
        <label for="dependencia" class="form-label">Filtrar por dependência</label>
        <select class="form-select" id="dependencia" name="dependencia">
          <option value="">Todas as dependências</option>
          <?php foreach ($dependencia_options as $dep): ?>
            <option value="<?php echo htmlspecialchars($dep); ?>" <?php echo $filtro_dependencia === $dep ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 d-grid">
        <button type="submit" class="btn btn-success"><i class="bi bi-funnel me-2"></i>Aplicar filtros</button>
      </div>
    </form>
  </div>

<!-- Cabeçalho do relatório -->
<div class="card mb-3">
  <div class="card-body text-center">
    <h5 class="mb-1 text-gradient">RELATÓRIO DE ALTERAÇÕES</h5>
    <div class="text-muted">Planilha: <?php echo htmlspecialchars($planilha['descricao']); ?></div>
    <div class="small text-muted">Gerado em <?php echo date('d/m/Y H:i:s'); ?></div>
  </div>
  <div class="card-footer">
    <div><strong>Status:</strong> <?php echo ucfirst($planilha['status']); ?></div>
  </div>
  </div>

<!-- Resumo -->
<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-graph-up-arrow me-2"></i> Resumo geral
  </div>
  <div class="card-body">
    <ul class="mb-0">
      <li><strong>Total de produtos:</strong> <?php echo $total_geral; ?></li>
      <li><strong>Checados:</strong> <?php echo $total_checados; ?></li>
      <li><strong>Com observação:</strong> <?php echo $total_observacao; ?></li>
      <li><strong>Checados + observação:</strong> <?php echo $total_checados_observacao; ?></li>
      <li><strong>DR:</strong> <?php echo $total_dr; ?></li>
      <li><strong>Etiqueta:</strong> <?php echo $total_etiqueta; ?></li>
      <li><strong>Pendentes:</strong> <?php echo $total_pendentes; ?></li>
      <li><strong>Com alterações:</strong> <?php echo $total_alteracoes; ?></li>
      <li class="mt-2"><strong>Total a ser impresso:</strong> <?php echo $total_mostrar; ?> produtos</li>
    </ul>
  </div>
  </div>

<?php if ($total_geral > 0 && $total_mostrar > 0): ?>
  <?php if ($mostrar_alteracoes && $total_alteracoes > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Produtos com alterações (<?php echo $total_alteracoes; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>Código</th><th>Nome Original</th><th>Novo Nome</th><th>Dependência Original</th><th>Nova Dependência</th><th>Observações</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($produtos_alteracoes as $produto): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                <td class="table-warning fw-semibold"><?php echo htmlspecialchars($produto['nome_editado'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                <td class="table-warning fw-semibold"><?php echo htmlspecialchars($produto['dependencia_editada'] ?? ''); ?></td>
                <td class="table-warning fst-italic"><?php echo htmlspecialchars($produto['observacoes'] ?? ''); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_pendentes && $total_pendentes > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Produtos pendentes (<?php echo $total_pendentes; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Código</th><th>Nome</th><th>Dependência</th></tr></thead>
            <tbody><?php foreach ($produtos_pendentes as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td><?php echo htmlspecialchars($produto['nome']); ?></td><td><?php echo htmlspecialchars($produto['dependencia']); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_checados && $total_checados > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Produtos checados (<?php echo $total_checados; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Código</th><th>Nome</th><th>Dependência</th></tr></thead>
            <tbody><?php foreach ($produtos_checados as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="table-success"><?php echo htmlspecialchars($produto['nome']); ?></td><td><?php echo htmlspecialchars($produto['dependencia']); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_observacao && $total_observacao > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Produtos com observação (<?php echo $total_observacao; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Código</th><th>Nome</th><th>Observações</th></tr></thead>
            <tbody><?php foreach ($produtos_observacao as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td><?php echo htmlspecialchars($produto['nome']); ?></td><td class="table-warning fst-italic"><?php echo htmlspecialchars($produto['observacoes']); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_checados_observacao && $total_checados_observacao > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Checados + observação (<?php echo $total_checados_observacao; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Código</th><th>Nome</th><th>Observações</th></tr></thead>
            <tbody><?php foreach ($produtos_checados_observacao as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="table-secondary"><?php echo htmlspecialchars($produto['nome']); ?></td><td class="table-secondary"><?php echo htmlspecialchars($produto['observacoes']); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_dr && $total_dr > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Produtos no DR (<?php echo $total_dr; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Código</th><th>Nome</th><th>Dependência</th><th>Observações</th></tr></thead>
            <tbody><?php foreach ($produtos_dr as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="table-danger"><?php echo htmlspecialchars($produto['nome']); ?></td><td><?php echo htmlspecialchars($produto['dependencia']); ?></td><td class="table-warning fst-italic"><?php echo htmlspecialchars($produto['observacoes'] ?? ''); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_etiqueta && $total_etiqueta > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Produtos com etiqueta (<?php echo $total_etiqueta; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Código</th><th>Nome</th><th>Dependência</th></tr></thead>
            <tbody><?php foreach ($produtos_etiqueta as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="table-info"><?php echo htmlspecialchars($produto['nome']); ?></td><td><?php echo htmlspecialchars($produto['dependencia']); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php elseif ($total_geral > 0 && $total_mostrar === 0): ?>
  <div class="alert alert-warning">
    <i class="bi bi-info-circle me-2"></i> Marque pelo menos uma seção para visualizar o relatório.
  </div>
<?php else: ?>
  <div class="alert alert-secondary">
    <i class="bi bi-emoji-frown me-2"></i> Nenhum produto encontrado para os filtros aplicados.
  </div>
<?php endif; ?>

<div class="text-center text-muted small my-3">
  Relatório gerado em <?php echo date('d/m/Y \à\s H:i:s'); ?>
  </div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_imprimir_alteracao_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

include __DIR__ . '/../layouts/app-wrapper.php';

unlink($tempFile);
?>
