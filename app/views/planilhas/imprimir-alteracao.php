<?php
require_once __DIR__ . '/../../../auth.php'; // Autentica├º├úo
// Agora: p├ígina integrada ao layout app-wrapper (Bootstrap 5, 400px)
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
    if (!$planilha) { throw new Exception('Planilha n├úo encontrada.'); }
} catch (Exception $e) { die("Erro ao carregar planilha: " . $e->getMessage()); }

$mostrar_pendentes = isset($_GET['mostrar_pendentes']);
$mostrar_checados = isset($_GET['mostrar_checados']);
$mostrar_observacao = isset($_GET['mostrar_observacao']);
$mostrar_checados_observacao = isset($_GET['mostrar_checados_observacao']);
$mostrar_dr = isset($_GET['mostrar_dr']);
$mostrar_etiqueta = isset($_GET['mostrar_etiqueta']);
$mostrar_alteracoes = isset($_GET['mostrar_alteracoes']);
$mostrar_novos = isset($_GET['mostrar_novos']);
$filtro_dependencia = isset($_GET['dependencia']) && $_GET['dependencia'] !== '' ? (int)$_GET['dependencia'] : '';

try {
    // Buscar produtos da planilha importada (tabela produtos)
    $sql_produtos = "SELECT p.*, 
                     CAST(p.checado AS SIGNED) as checado, 
                     CAST(p.ativo AS SIGNED) as ativo, 
                     CAST(p.imprimir_etiqueta AS SIGNED) as imprimir, 
                     p.observacao as observacoes, 
                     CAST(p.editado AS SIGNED) as editado, 
                     p.editado_descricao_completa as nome_editado, 
                     p.editado_dependencia_id as dependencia_editada,
                     'planilha' as origem
                     FROM produtos p 
                     WHERE p.planilha_id = :id_planilha";
    $params = [':id_planilha' => $id_planilha];
    if (!empty($filtro_dependencia)) { 
        $sql_produtos .= " AND (
            (CAST(p.editado AS SIGNED) = 1 AND p.editado_dependencia_id = :dependencia) OR
            (CAST(p.editado AS SIGNED) IS NULL OR CAST(p.editado AS SIGNED) = 0) AND p.dependencia_id = :dependencia
        )"; 
        $params[':dependencia'] = $filtro_dependencia; 
    }
    $sql_produtos .= " ORDER BY p.codigo";
    $stmt_produtos = $conexao->prepare($sql_produtos);
    foreach ($params as $k => $v) { $stmt_produtos->bindValue($k, $v); }
    $stmt_produtos->execute();
    $todos_produtos = $stmt_produtos->fetchAll();
    
    // Buscar produtos novos cadastrados manualmente (tabela produtos_cadastro n├úo existe no schema atual)
    // $sql_novos = "SELECT pc.id, pc.id_planilha, pc.descricao_completa as nome, '' as codigo, pc.complemento as dependencia,
    //               pc.quantidade, pc.tipo_ben, pc.imprimir_14_1 as imprimir_cadastro, 'cadastro' as origem,
    //               NULL as checado, 1 as ativo, NULL as imprimir, NULL as observacoes, NULL as editado, NULL as nome_editado, NULL as dependencia_editada
    //               FROM produtos_cadastro pc
    //               WHERE pc.id_planilha = :id_planilha";
    // $params_novos = [':id_planilha' => $id_planilha];
    // if (!empty($filtro_dependencia)) { $sql_novos .= " AND pc.complemento LIKE :dependencia"; $params_novos[':dependencia'] = '%' . $filtro_dependencia . '%'; }
    // $sql_novos .= " ORDER BY pc.id";
    // $stmt_novos = $conexao->prepare($sql_novos);
    // foreach ($params_novos as $k => $v) { $stmt_novos->bindValue($k, $v); }
    // $stmt_novos->execute();
    // $produtos_cadastrados = $stmt_novos->fetchAll();

    // Combinar ambos os arrays (removido pois tabela produtos_cadastro n├úo existe)
    // $todos_produtos = array_merge($todos_produtos, $produtos_cadastrados);
} catch (Exception $e) { die("Erro ao carregar produtos: " . $e->getMessage()); }

try {
    // Buscar depend├¬ncias originais + depend├¬ncias editadas
    $sql_dependencias = "
        SELECT DISTINCT p.dependencia_id as dependencia FROM produtos p WHERE p.planilha_id = :id_planilha1
        UNION
        SELECT DISTINCT p.editado_dependencia_id as dependencia FROM produtos p
        WHERE p.planilha_id = :id_planilha2 AND p.editado = 1 AND p.editado_dependencia_id IS NOT NULL
        ORDER BY dependencia
    ";
    $stmt_dependencias = $conexao->prepare($sql_dependencias);
    $stmt_dependencias->bindValue(':id_planilha1', $id_planilha);
    $stmt_dependencias->bindValue(':id_planilha2', $id_planilha);
    $stmt_dependencias->execute();
    $dependencia_options = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $dependencia_options = []; }

// Mapear ID -> descri├º├úo/c├│digo para exibir label amig├ível no filtro
$dependencias_map = [];
if (!empty($dependencia_options)) {
    $placeholders = implode(',', array_fill(0, count($dependencia_options), '?'));
    $stmtDepMap = $conexao->prepare("SELECT id, codigo, descricao FROM dependencias WHERE id IN ($placeholders)");
    foreach ($dependencia_options as $idx => $depId) {
        $stmtDepMap->bindValue($idx + 1, (int)$depId, PDO::PARAM_INT);
    }
    if ($stmtDepMap->execute()) {
        foreach ($stmtDepMap->fetchAll(PDO::FETCH_ASSOC) as $d) {
            $dependencias_map[(int)$d['id']] = [
                'codigo' => $d['codigo'],
                'descricao' => $d['descricao']
            ];
        }
    }
}

$produtos_pendentes = $produtos_checados = $produtos_observacao = $produtos_checados_observacao = $produtos_dr = $produtos_etiqueta = $produtos_alteracoes = $produtos_novos = [];
foreach ($todos_produtos as $produto) {
    // Nome atual: usa descrição editada se existir, senão a descrição completa original
    $nome_editado = trim($produto['nome_editado'] ?? '');
    $nome_original = trim($produto['descricao_completa'] ?? ($produto['nome'] ?? ''));
    $nome_atual = $nome_editado !== '' ? $nome_editado : $nome_original;
    $produto['nome_atual'] = $nome_atual !== '' ? $nome_atual : 'Sem descricao';
    $produto['nome_original'] = $nome_original;

    // Produtos novos = vindos da tabela produtos_cadastro
    if (($produto['origem'] ?? '') === 'cadastro') {
        $produtos_novos[] = $produto;
        if (!empty($produto['codigo'])) {
            $produtos_etiqueta[] = $produto; // novos com código também vão para etiqueta
        }
        continue;
    }
    
    // Produtos da planilha importada (tabela produtos)
    $tem_observacao = !empty($produto['observacoes']);
    $esta_checado = ($produto['checado'] ?? 0) == 1;
    $esta_no_dr = ($produto['ativo'] ?? 1) == 0;
    $esta_etiqueta = ($produto['imprimir'] ?? 0) == 1;
    $tem_alteracoes = (int)($produto['editado'] ?? 0) === 1;
    $eh_pendente = is_null($produto['checado']) && ($produto['ativo'] ?? 1) == 1 && is_null($produto['imprimir']) && is_null($produto['observacoes']) && is_null($produto['editado']);
    
    if ($tem_alteracoes) {
        // Editados aparecem aqui e também mantêm sua seção de status
        $produtos_alteracoes[] = $produto;
        $produtos_etiqueta[] = $produto;
    }

    if ($esta_no_dr) {
        $produtos_dr[] = $produto;
    } elseif ($esta_etiqueta) {
        $produtos_etiqueta[] = $produto;
    } elseif ($tem_observacao && $esta_checado) {
        $produtos_checados_observacao[] = $produto;
    } elseif ($tem_observacao) {
        $produtos_observacao[] = $produto;
    } elseif ($esta_checado) {
        $produtos_checados[] = $produto;
    } elseif ($eh_pendente) {
        $produtos_pendentes[] = $produto;
    } else {
        $produtos_pendentes[] = $produto;
    }
}$total_pendentes = count($produtos_pendentes);
$total_checados = count($produtos_checados);
$total_observacao = count($produtos_observacao);
$total_checados_observacao = count($produtos_checados_observacao);
$total_dr = count($produtos_dr);
$total_etiqueta = count($produtos_etiqueta);
$total_alteracoes = count($produtos_alteracoes);
$total_novos = count($produtos_novos);
$total_geral = count($todos_produtos);

// DEBUG: Verificar produtos com editado = 1
if (isset($_GET['debug'])) {
    echo "<pre>DEBUG - Produtos com editado:<br>";
    foreach ($todos_produtos as $p) {
        if (($p['origem'] ?? '') !== 'cadastro') {
            $editado_valor = $p['editado'] ?? 'NULL';
            $editado_tipo = gettype($p['editado'] ?? null);
            $tem_nome_editado = !empty($p['nome_editado']) ? 'SIM' : 'N├âO';
            $tem_dep_editada = !empty($p['dependencia_editada']) ? 'SIM' : 'N├âO';
            if ((int)($p['editado'] ?? 0) === 1 || !empty($p['nome_editado']) || !empty($p['dependencia_editada'])) {
                echo "ID: {$p['id']} | C├│digo: {$p['codigo']} | editado={$editado_valor} (tipo: {$editado_tipo}) | nome_editado: {$tem_nome_editado} | dep_editada: {$tem_dep_editada}<br>";
            }
        }
    }
    echo "Total em \$produtos_alteracoes: " . count($produtos_alteracoes) . "<br>";
    echo "</pre>";
}

$total_mostrar = 0;
if ($mostrar_pendentes) $total_mostrar += $total_pendentes;
if ($mostrar_checados) $total_mostrar += $total_checados;
if ($mostrar_observacao) $total_mostrar += $total_observacao;
if ($mostrar_checados_observacao) $total_mostrar += $total_checados_observacao;
if ($mostrar_dr) $total_mostrar += $total_dr;
if ($mostrar_etiqueta) $total_mostrar += $total_etiqueta;
if ($mostrar_alteracoes) $total_mostrar += $total_alteracoes;
if ($mostrar_novos) $total_mostrar += $total_novos;

// Cabe├ºalho do layout
$pageTitle = 'Imprimir Altera├º├Áes';
$backUrl = '../planilhas/view-planilha.php?id=' . $id_planilha;
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuAlteracao" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuAlteracao">
            <li>
                <button class="dropdown-item" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i>Imprimir
                </button>
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

// CSS de impress├úo e ajustes para o wrapper mobile
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

// Conte├║do da p├ígina
ob_start();
?>

<!-- Filtros -->
<div class="card mb-3 no-print">
  <div class="card-header">
    <i class="bi bi-filter-circle me-2"></i> Filtros do relat├│rio
  </div>
  <div class="card-body">
    <form method="GET" class="row g-3">
      <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
      <div class="col-12">
  <label class="form-label">Se├º├Áes a incluir</label>
        <div class="row g-2">
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secPend" name="mostrar_pendentes" value="1" <?php echo $mostrar_pendentes ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secPend">Pendentes (<?php echo $total_pendentes; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secChec" name="mostrar_checados" value="1" <?php echo $mostrar_checados ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secChec">Checados (<?php echo $total_checados; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secObs" name="mostrar_observacao" value="1" <?php echo $mostrar_observacao ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secObs">Com Observa├º├úo (<?php echo $total_observacao; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secChecObs" name="mostrar_checados_observacao" value="1" <?php echo $mostrar_checados_observacao ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secChecObs">Checados com Observa├º├úo (<?php echo $total_checados_observacao; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secDR" name="mostrar_dr" value="1" <?php echo $mostrar_dr ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secDR">Devolu├º├úo (DR) (<?php echo $total_dr; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secEtiq" name="mostrar_etiqueta" value="1" <?php echo $mostrar_etiqueta ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secEtiq">Para Etiqueta (<?php echo $total_etiqueta; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secAlt" name="mostrar_alteracoes" value="1" <?php echo $mostrar_alteracoes ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secAlt">Editados (<?php echo $total_alteracoes; ?>)</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="secNovos" name="mostrar_novos" value="1" <?php echo $mostrar_novos ? 'checked' : ''; ?>>
              <label class="form-check-label" for="secNovos">Cadastrados Novos (<?php echo $total_novos; ?>)</label>
            </div>
          </div>
        </div>
      </div>
      <div class="col-12">
        <label for="dependencia" class="form-label">Filtrar por depend├¬ncia</label>
        <select class="form-select" id="dependencia" name="dependencia">
          <option value="">Todas as depend├¬ncias</option>
          <?php foreach ($dependencia_options as $dep): ?>
            <?php 
                $depId = (int)$dep;
                $label = $dependencias_map[$depId]['descricao'] ?? $depId;
                if (isset($dependencias_map[$depId]['codigo'])) {
                    $label = $dependencias_map[$depId]['codigo'] . ' - ' . $label;
                }
            ?>
            <option value="<?php echo $depId; ?>" <?php echo ($filtro_dependencia === $depId) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($label); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 d-grid">
        <button type="submit" class="btn btn-success"><i class="bi bi-funnel me-2"></i>Aplicar filtros</button>
      </div>
    </form>
  </div>
</div>

<!-- Cabe├ºalho do relat├│rio -->
<div class="card mb-3">
  <div class="card-body text-center">
    <h5 class="mb-1 text-gradient">RELAT├ôRIO DE ALTERA├ç├òES</h5>
    <div class="text-muted">Planilha: <?php echo htmlspecialchars($planilha['comum']); ?></div>
    <div class="small text-muted">Gerado em <?php echo date('d/m/Y H:i:s'); ?></div>
  </div>
  <div class="card-footer">
    <?php
      // Status din├ómico da planilha com base nos totais
      if ($total_pendentes === $total_geral && $total_novos === 0) {
        $status_calc = 'Pendente';
        $badge = 'secondary';
      } elseif ($total_pendentes === 0) {
        $status_calc = 'Conclu├¡da';
        $badge = 'success';
      } else {
        $status_calc = 'Em Execu├º├úo';
        $badge = 'warning text-dark';
      }
    ?>
    <div><strong>Status:</strong> <span class="badge bg-<?php echo $badge; ?>"><?php echo $status_calc; ?></span></div>
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
      <li><strong>Com observa├º├úo:</strong> <?php echo $total_observacao; ?></li>
      <li><strong>Checados + observa├º├úo:</strong> <?php echo $total_checados_observacao; ?></li>
      <li><strong>DR:</strong> <?php echo $total_dr; ?></li>
      <li><strong>Etiqueta:</strong> <?php echo $total_etiqueta; ?></li>
      <li><strong>Pendentes:</strong> <?php echo $total_pendentes; ?></li>
      <li><strong>Com altera├º├Áes:</strong> <?php echo $total_alteracoes; ?></li>
      <li><strong>Novos:</strong> <?php echo $total_novos; ?></li>
      <li class="mt-2"><strong>Total a ser impresso:</strong> <?php echo $total_mostrar; ?> produtos</li>
    </ul>
  </div>
  </div>

<?php if ($total_geral > 0 && $total_mostrar > 0): ?>
  <?php if ($mostrar_alteracoes && $total_alteracoes > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Produtos com altera├º├Áes (<?php echo $total_alteracoes; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead>
              <tr>
                <th>C├│digo</th>
                <th>Antigo</th>
                <th>Novo</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($produtos_alteracoes as $produto): ?>
              <?php
                // Construir texto antigo e novo
                $antigo = [];
                $novo = [];
                
                                // Verificar alteração no nome (usar descrições completas já montadas)
                $nome_original = $produto['nome_original'] ?? ($produto['nome'] ?? '');
                $nome_atual = $produto['nome_atual'] ?? $nome_original;
                if (!empty($produto['nome_editado']) && $produto['nome_editado'] != $nome_original) {
                    $antigo[] = htmlspecialchars($nome_original);
                    $novo[] = htmlspecialchars($nome_atual);
                } else {
                    // Se não mudou, mostrar o nome atual em ambas as colunas
                    $antigo[] = htmlspecialchars($nome_atual);
                    $novo[] = htmlspecialchars($nome_atual);
                }
                
                $texto_antigo = implode('<br>', $antigo);
                $texto_novo = implode('<br>', $novo);
              ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                <td><?php echo $texto_antigo; ?></td>
                <td class="table-warning"><?php echo $texto_novo; ?></td>
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
      <div class="card-header">Pendentes (<?php echo $total_pendentes; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>C├│digo</th><th>Descrição</th><th>Depend├¬ncia</th></tr></thead>
            <tbody><?php foreach ($produtos_pendentes as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td><?php echo htmlspecialchars($produto['nome_atual']); ?></td><td><?php echo htmlspecialchars($produto['dependencia'] ?? ''); ?></td></tr><?php endforeach; ?></tbody>
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
            <thead><tr><th>C├│digo</th><th>Descrição</th><th>Depend├¬ncia</th></tr></thead>
            <tbody><?php foreach ($produtos_checados as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="table-success"><?php echo htmlspecialchars($produto['nome_atual']); ?></td><td><?php echo htmlspecialchars($produto['dependencia'] ?? ''); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_observacao && $total_observacao > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Produtos com observa├º├úo (<?php echo $total_observacao; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>C├│digo</th><th>Descrição</th><th>Observa├º├Áes</th></tr></thead>
            <tbody><?php foreach ($produtos_observacao as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td><?php echo htmlspecialchars($produto['nome_atual']); ?></td><td class="table-warning fst-italic"><?php echo htmlspecialchars($produto['observacoes']); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_checados_observacao && $total_checados_observacao > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Checados + observa├º├úo (<?php echo $total_checados_observacao; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>C├│digo</th><th>Descrição</th><th>Observa├º├Áes</th></tr></thead>
            <tbody><?php foreach ($produtos_checados_observacao as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="table-secondary"><?php echo htmlspecialchars($produto['nome_atual']); ?></td><td class="table-secondary"><?php echo htmlspecialchars($produto['observacoes']); ?></td></tr><?php endforeach; ?></tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_dr && $total_dr > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Devolu├º├úo (DR) (<?php echo $total_dr; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>C├│digo</th><th>Descrição</th></tr></thead>
            <tbody>
            <?php foreach ($produtos_dr as $produto): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                <td class="table-danger"><?php echo htmlspecialchars($produto['nome_atual']); ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_etiqueta && $total_etiqueta > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Para Etiqueta (<?php echo $total_etiqueta; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>C├│digo</th><th>Descrição</th></tr></thead>
            <tbody>
              <?php foreach ($produtos_etiqueta as $produto): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
                <td class="table-success"><?php echo htmlspecialchars($produto['nome_atual']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($mostrar_novos && $total_novos > 0): ?>
    <div class="card mb-3">
      <div class="card-header">Cadastrados Novos (<?php echo $total_novos; ?>)</div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle mb-0">
            <thead><tr><th>Descri├º├úo Completa</th><th class="text-center">Quantidade</th></tr></thead>
            <tbody>
              <?php foreach ($produtos_novos as $produto): ?>
                <tr>
                  <td class="table-success"><strong><?php echo htmlspecialchars($produto['nome_atual']); ?></strong></td>
                  <td class="text-center"><?php echo htmlspecialchars($produto['quantidade'] ?? 'ÔÇö'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php elseif ($total_geral > 0 && $total_mostrar === 0): ?>
  <div class="alert alert-warning">
    <i class="bi bi-info-circle me-2"></i> Marque pelo menos uma se├º├úo para visualizar o relat├│rio.
  </div>
<?php else: ?>
  <div class="alert alert-secondary">
    <i class="bi bi-emoji-frown me-2"></i> Nenhum produto encontrado para os filtros aplicados.
  </div>
<?php endif; ?>

<div class="text-center text-muted small my-3">
  Relat├│rio gerado em <?php echo date('d/m/Y \├á\s H:i:s'); ?>
  </div>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_imprimir_alteracao_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

include __DIR__ . '/../layouts/app-wrapper.php';

unlink($tempFile);
?>


















