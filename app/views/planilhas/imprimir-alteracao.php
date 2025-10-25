<?php
// Página standalone de impressão (relatório de alterações) – mantém layout próprio
require_once __DIR__ . '/../../../CRUD/conexao.php';

$id_planilha = $_GET['id'] ?? null;
if (!$id_planilha) { header('Location: ../../index.php'); exit; }

// Reutiliza integralmente a lógica existente do arquivo original
// Copiamos o conteúdo para manter 100% das funcionalidades e o estilo de impressão
?>
<?php
// Início do conteúdo original
?>
<?php
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
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Impressão de Alterações - <?php echo htmlspecialchars($planilha['descricao']); ?></title>
  <style>
  <?php /* CSS mantido do arquivo original */ ?>
  body { font-family: Arial, sans-serif; margin:0; padding:0; color:#000; font-size:12px; line-height:1.4; }
  @media print { body { padding:10px; font-size:10px; } .no-print{display:none!important;} .page-break{page-break-after:always;} table{page-break-inside:auto;} tr{page-break-inside:avoid; page-break-after:auto;} .filtros-info{background:#f8f9fa!important;color:#000!important;border:1px solid #000!important;} }
  header{ background:#007bff; padding:5px 10px; color:#fff; display:flex; align-items:center; justify-content:space-between; height:50px; }
  .header-title{ width:70%; font-size:16px; margin:0; text-align:center; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .header-actions{ width:30%; display:flex; align-items:center; justify-content:flex-end; gap:10px; }
  .header-btn,.header-print{ background:none; border:none; color:#fff; cursor:pointer; padding:8px; border-radius:4px; font-size:20px; display:flex; align-items:center; justify-content:center; transition:background-color .2s; text-decoration:none; }
  .header-btn:hover,.header-print:hover{ background-color:rgba(255,255,255,.2); }
  .info-planilha{ margin-bottom:15px; padding:10px; background:#f5f5f5; border-left:4px solid #007bff; }
  .filtros-form{ background:#f8f9fa; padding:20px; border-radius:5px; margin-bottom:20px; border:1px solid #dee2e6; }
  .filtro-group{ margin-bottom:15px; } .filtro-group label{ display:block; margin-bottom:5px; font-weight:bold; }
  .filtro-options{ display:flex; flex-wrap:wrap; gap:15px; } .filtro-option{ display:flex; align-items:center; gap:5px; }
  .btn-apply{ background:#28a745; color:#fff; padding:8px 16px; border:none; border-radius:4px; cursor:pointer; } .btn-apply:hover{ background:#218838; }
  .resumo{ margin-bottom:20px; padding:15px; background:#e9ecef; border-radius:4px; border:1px solid #ced4da; }
  table{ width:100%; border-collapse:collapse; margin-bottom:20px; } th{ background:#343a40; color:#fff; padding:8px; text-align:left; border:1px solid #000; font-size:11px; } td{ padding:6px; border:1px solid #000; vertical-align:top; }
  .observacao-cell{ background:#fff3cd; font-style:italic; } .checado-cell{ background:#d4edda; } .ambos-cell{ background:#e6e6fa; } .dr-cell{ background:#f8d7da; } .etiqueta-cell{ background:#cce7ff; } .alteracao-cell{ background:#fff3cd; font-weight:bold; }
  .secao-titulo{ background:#6c757d; color:#fff; padding:8px; margin:20px 0 10px 0; font-weight:bold; border-left:4px solid #495057; page-break-before:always; }
  .sem-registros{ text-align:center; padding:20px; background:#f8f9fa; border:1px dashed #6c757d; margin:10px 0; }
  .footer{ margin-top:30px; padding-top:10px; border-top:1px solid #000; text-align:center; font-size:10px; }
  </style>
</head>
<body>
  <header class="no-print">
    <a href="../planilhas/view-planilha.php?id=<?php echo $id_planilha; ?>" class="header-btn" title="Fechar">❌</a>
    <h1 class="header-title">Impressão de Alterações</h1>
    <div class="header-actions">
      <button class="header-print" onclick="window.print()" title="Imprimir">🖨️</button>
    </div>
  </header>

  <div class="filtros-form no-print">
    <h3>Seções do Relatório (Marque quais seções deseja incluir)</h3>
    <form method="GET">
      <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
      <div class="filtro-group">
        <label>Seções a Incluir:</label>
        <div class="filtro-options">
          <label class="filtro-option"><input type="checkbox" name="mostrar_pendentes" value="1" <?php echo $mostrar_pendentes ? 'checked' : ''; ?>> Imprimir produtos pendentes (<?php echo $total_pendentes; ?>)</label>
          <label class="filtro-option"><input type="checkbox" name="mostrar_checados" value="1" <?php echo $mostrar_checados ? 'checked' : ''; ?>> Imprimir produtos checados (<?php echo $total_checados; ?>)</label>
          <label class="filtro-option"><input type="checkbox" name="mostrar_observacao" value="1" <?php echo $mostrar_observacao ? 'checked' : ''; ?>> Imprimir produtos com observação (<?php echo $total_observacao; ?>)</label>
          <label class="filtro-option"><input type="checkbox" name="mostrar_checados_observacao" value="1" <?php echo $mostrar_checados_observacao ? 'checked' : ''; ?>> Imprimir produtos checados + observação (<?php echo $total_checados_observacao; ?>)</label>
          <label class="filtro-option"><input type="checkbox" name="mostrar_dr" value="1" <?php echo $mostrar_dr ? 'checked' : ''; ?>> Imprimir produtos do DR (<?php echo $total_dr; ?>)</label>
          <label class="filtro-option"><input type="checkbox" name="mostrar_etiqueta" value="1" <?php echo $mostrar_etiqueta ? 'checked' : ''; ?>> Imprimir produtos de etiqueta (<?php echo $total_etiqueta; ?>)</label>
          <label class="filtro-option"><input type="checkbox" name="mostrar_alteracoes" value="1" <?php echo $mostrar_alteracoes ? 'checked' : ''; ?>> Imprimir produtos com alterações (<?php echo $total_alteracoes; ?>)</label>
        </div>
      </div>
      <div class="filtro-group">
        <label for="dependencia">Filtrar por Dependência:</label>
        <select name="dependencia" id="dependencia" style="padding:5px; width:300px;">
          <option value="">Todas as dependências</option>
          <?php foreach ($dependencia_options as $dep): ?>
            <option value="<?php echo htmlspecialchars($dep); ?>" <?php echo $filtro_dependencia === $dep ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-apply">Aplicar Filtros</button>
    </form>
  </div>

  <div class="header-impressao" style="text-align:center; margin-bottom:20px; border-bottom:2px solid #000; padding-bottom:10px;">
    <h1 style="margin:0; font-size:18px;">RELATÓRIO DE ALTERAÇÕES - CONTROLE DE PATRIMÔNIO</h1>
    <h2 style="margin:5px 0; font-size:14px; font-weight:normal;">Planilha: <?php echo htmlspecialchars($planilha['descricao']); ?></h2>
    <p style="margin:0;">Data de geração: <?php echo date('d/m/Y H:i:s'); ?></p>
  </div>

  <div class="info-planilha"><strong>Status da Planilha:</strong> <?php echo ucfirst($planilha['status']); ?></div>

  <div class="resumo">
    <h3 style="margin-top:0; color:#007bff;">RESUMO GERAL</h3>
    <p><strong>Total de produtos na planilha:</strong> <?php echo $total_geral; ?></p>
    <p><strong>Produtos checados:</strong> <?php echo $total_checados; ?></p>
    <p><strong>Produtos com observação:</strong> <?php echo $total_observacao; ?></p>
    <p><strong>Produtos checado + observação:</strong> <?php echo $total_checados_observacao; ?></p>
    <p><strong>Produtos do DR:</strong> <?php echo $total_dr; ?></p>
    <p><strong>Produtos com etiqueta:</strong> <?php echo $total_etiqueta; ?></p>
    <p><strong>Produtos pendentes:</strong> <?php echo $total_pendentes; ?></p>
    <p><strong>Produtos com alterações:</strong> <?php echo $total_alteracoes; ?></p>
    <p style="font-weight:bold; border-top:1px solid #ccc; padding-top:8px; margin-top:8px;">Total a ser impresso: <?php echo $total_mostrar; ?> produtos</p>
  </div>

  <?php if ($total_geral > 0 && $total_mostrar > 0): ?>
    <?php if ($mostrar_alteracoes && $total_alteracoes > 0): ?>
      <div class="secao-titulo">PRODUTOS COM ALTERAÇÕES (<?php echo $total_alteracoes; ?> itens)</div>
      <table>
        <thead><tr><th width="15%">Código</th><th width="25%">Nome Original</th><th width="25%">Novo Nome</th><th width="20%">Dependência Original</th><th width="20%">Nova Dependência</th><th width="20%">Observações</th></tr></thead>
        <tbody>
          <?php foreach ($produtos_alteracoes as $produto): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td>
              <td><?php echo htmlspecialchars($produto['nome']); ?></td>
              <td class="alteracao-cell"><?php echo htmlspecialchars($produto['nome_editado'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
              <td class="alteracao-cell"><?php echo htmlspecialchars($produto['dependencia_editada'] ?? ''); ?></td>
              <td class="observacao-cell"><?php echo htmlspecialchars($produto['observacoes'] ?? ''); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <?php if ($mostrar_pendentes && $total_pendentes > 0): ?>
      <div class="secao-titulo">PRODUTOS PENDENTES (<?php echo $total_pendentes; ?> itens)</div>
      <table>
        <thead><tr><th width="20%">Código</th><th width="50%">Nome</th><th width="30%">Dependência</th></tr></thead>
        <tbody><?php foreach ($produtos_pendentes as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td><?php echo htmlspecialchars($produto['nome']); ?></td><td><?php echo htmlspecialchars($produto['dependencia']); ?></td></tr><?php endforeach; ?></tbody>
      </table>
    <?php endif; ?>

    <?php if ($mostrar_checados && $total_checados > 0): ?>
      <div class="secao-titulo">PRODUTOS CHECADOS (<?php echo $total_checados; ?> itens)</div>
      <table>
        <thead><tr><th width="20%">Código</th><th width="50%">Nome</th><th width="30%">Dependência</th></tr></thead>
        <tbody><?php foreach ($produtos_checados as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="checado-cell"><?php echo htmlspecialchars($produto['nome']); ?></td><td><?php echo htmlspecialchars($produto['dependencia']); ?></td></tr><?php endforeach; ?></tbody>
      </table>
    <?php endif; ?>

    <?php if ($mostrar_observacao && $total_observacao > 0): ?>
      <div class="secao-titulo">PRODUTOS COM OBSERVAÇÃO (<?php echo $total_observacao; ?> itens)</div>
      <table>
        <thead><tr><th width="20%">Código</th><th width="40%">Nome</th><th width="40%">Observações</th></tr></thead>
        <tbody><?php foreach ($produtos_observacao as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td><?php echo htmlspecialchars($produto['nome']); ?></td><td class="observacao-cell"><?php echo htmlspecialchars($produto['observacoes']); ?></td></tr><?php endforeach; ?></tbody>
      </table>
    <?php endif; ?>

    <?php if ($mostrar_checados_observacao && $total_checados_observacao > 0): ?>
      <div class="secao-titulo">PRODUTOS CHECADOS + OBSERVAÇÃO (<?php echo $total_checados_observacao; ?> itens)</div>
      <table>
        <thead><tr><th width="20%">Código</th><th width="40%">Nome</th><th width="40%">Observações</th></tr></thead>
        <tbody><?php foreach ($produtos_checados_observacao as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="ambos-cell"><?php echo htmlspecialchars($produto['nome']); ?></td><td class="ambos-cell"><?php echo htmlspecialchars($produto['observacoes']); ?></td></tr><?php endforeach; ?></tbody>
      </table>
    <?php endif; ?>

    <?php if ($mostrar_dr && $total_dr > 0): ?>
      <div class="secao-titulo">PRODUTOS NO DR (<?php echo $total_dr; ?> itens)</div>
      <table>
        <thead><tr><th width="15%">Código</th><th width="35%">Nome</th><th width="25%">Dependência</th><th width="25%">Observações</th></tr></thead>
        <tbody><?php foreach ($produtos_dr as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="dr-cell"><?php echo htmlspecialchars($produto['nome']); ?></td><td><?php echo htmlspecialchars($produto['dependencia']); ?></td><td class="observacao-cell"><?php echo htmlspecialchars($produto['observacoes'] ?? ''); ?></td></tr><?php endforeach; ?></tbody>
      </table>
    <?php endif; ?>

    <?php if ($mostrar_etiqueta && $total_etiqueta > 0): ?>
      <div class="secao-titulo">PRODUTOS COM ETIQUETA (<?php echo $total_etiqueta; ?> itens)</div>
      <table>
        <thead><tr><th width="20%">Código</th><th width="50%">Nome</th><th width="30%">Dependência</th></tr></thead>
        <tbody><?php foreach ($produtos_etiqueta as $produto): ?><tr><td><strong><?php echo htmlspecialchars($produto['codigo']); ?></strong></td><td class="etiqueta-cell"><?php echo htmlspecialchars($produto['nome']); ?></td><td><?php echo htmlspecialchars($produto['dependencia']); ?></td></tr><?php endforeach; ?></tbody>
      </table>
    <?php endif; ?>
  <?php elseif ($total_geral > 0 && $total_mostrar === 0): ?>
    <div class="sem-registros" style="text-align:center; padding:40px;">
      <h3>Nenhuma seção selecionada</h3>
      <p>Marque pelo menos uma seção para visualizar o relatório.</p>
    </div>
  <?php else: ?>
    <div class="sem-registros" style="text-align:center; padding:40px;">
      <h3>Nenhum produto encontrado</h3>
      <p>Não há produtos cadastrados nesta planilha ou não correspondem ao filtro de dependência.</p>
    </div>
  <?php endif; ?>

  <div class="footer">Relatório gerado em <?php echo date('d/m/Y \à\s H:i:s'); ?> | Sistema de Controle de Patrimônio</div>

  <script>window.onbeforeprint = function(){ document.title = "Relatório Alterações - <?php echo htmlspecialchars($planilha['descricao']); ?>"; };</script>
</body>
</html>
