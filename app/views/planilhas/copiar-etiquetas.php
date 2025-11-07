<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
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
    if (!$planilha) throw new Exception('Planilha não encontrada.');
} catch (Exception $e) {
    die('Erro ao carregar planilha: ' . $e->getMessage());
}

// Dependências disponíveis
try {
    $sql_dependencias = "
        SELECT DISTINCT dependencia FROM produtos WHERE id_planilha = :id_planilha1
        UNION
        SELECT DISTINCT pc.dependencia FROM produtos_check pc
        INNER JOIN produtos p ON pc.produto_id = p.id
        WHERE p.id_planilha = :id_planilha2 AND pc.editado = 1 AND pc.dependencia IS NOT NULL
        ORDER BY dependencia
    ";
    $stmt_dependencias = $conexao->prepare($sql_dependencias);
    $stmt_dependencias->bindValue(':id_planilha1', $id_planilha);
    $stmt_dependencias->bindValue(':id_planilha2', $id_planilha);
    $stmt_dependencias->execute();
    $dependencias = $stmt_dependencias->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { $dependencias = []; }

$dependencia_selecionada = $_GET['dependencia'] ?? '';

// Produtos marcados para imprimir (produtos checados)
try {
    $sql_produtos = "SELECT p.codigo, COALESCE(pc.dependencia, p.dependencia) as dependencia
                     FROM produtos p 
                     INNER JOIN produtos_check pc ON p.id = pc.produto_id 
                     WHERE p.id_planilha = :id_planilha AND pc.imprimir = 1";
    if (!empty($dependencia_selecionada)) {
        $sql_produtos .= " AND (
            (CAST(pc.editado AS SIGNED) = 1 AND pc.dependencia = :dependencia) OR
            (CAST(pc.editado AS SIGNED) IS NULL OR CAST(pc.editado AS SIGNED) = 0) AND p.dependencia = :dependencia
        )";
    }
    $sql_produtos .= " ORDER BY p.codigo";
    $stmt_produtos = $conexao->prepare($sql_produtos);
    $stmt_produtos->bindValue(':id_planilha', $id_planilha);
    if (!empty($dependencia_selecionada)) { $stmt_produtos->bindValue(':dependencia', $dependencia_selecionada); }
    $stmt_produtos->execute();
    $produtos = $stmt_produtos->fetchAll(PDO::FETCH_ASSOC);
    
    // Buscar também produtos cadastrados (novos) com código preenchido
    $sql_novos = "SELECT pc.codigo, d.descricao as dependencia
                  FROM produtos_cadastro pc
                  LEFT JOIN dependencias d ON pc.id_dependencia = d.id
                  WHERE pc.id_planilha = :id_planilha 
                  AND pc.codigo IS NOT NULL 
                  AND pc.codigo != ''";
    if (!empty($dependencia_selecionada)) {
        $sql_novos .= " AND d.descricao = :dependencia";
    }
    $sql_novos .= " ORDER BY pc.codigo";
    $stmt_novos = $conexao->prepare($sql_novos);
    $stmt_novos->bindValue(':id_planilha', $id_planilha);
    if (!empty($dependencia_selecionada)) { $stmt_novos->bindValue(':dependencia', $dependencia_selecionada); }
    $stmt_novos->execute();
    $produtos_novos = $stmt_novos->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar produtos checados e novos
    $produtos = array_merge($produtos, $produtos_novos);
    
    $codigos = array_column($produtos, 'codigo');
    $produtos_sem_espacos = array_map(fn($c) => str_replace(' ', '', $c), $codigos);
    $codigos_concatenados = implode(',', $produtos_sem_espacos);
} catch (Exception $e) {
    $codigos_concatenados = '';
    $produtos = [];
    $mensagem = 'Erro ao carregar produtos: ' . $e->getMessage();
}

$pageTitle = 'Copiar Etiquetas';
$backUrl = '../planilhas/view-planilha.php?id=' . urlencode($id_planilha);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuEtiquetas" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuEtiquetas">
            <li>
                <a class="dropdown-item" href="../planilhas/view-planilha.php?id=' . $id_planilha . '">
                    <i class="bi bi-eye me-2"></i>Visualizar Planilha
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

<?php if (!empty($mensagem)): ?>
  <div class="alert alert-danger"><?php echo $mensagem; ?></div>
<?php endif; ?>

<div class="card mb-3">
  <div class="card-header">
    <i class="bi bi-tag me-2"></i>
    Códigos para Impressão de Etiquetas
  </div>
  <div class="card-body">
    <p class="text-muted small mb-3">
      Lista com os códigos dos produtos marcados como "Para Imprimir" e dos produtos novos cadastrados com código preenchido.
    </p>

    <?php if (!empty($dependencias)): ?>
      <div class="mb-3">
        <label for="filtroDependencia" class="form-label">Filtrar por dependência</label>
        <select class="form-select" id="filtroDependencia" onchange="filtrarPorDependencia()">
          <option value="">Todas as dependências</option>
          <?php foreach ($dependencias as $dep): ?>
            <option value="<?php echo htmlspecialchars($dep); ?>" <?php echo ($dependencia_selecionada === $dep) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="row g-2 small">
      <div class="col-6">
        <div class="card shadow-sm-custom">
          <div class="card-body text-center">
            <div class="h4 mb-0"><?php echo count($produtos); ?></div>
            <div class="text-muted">Produtos</div>
          </div>
        </div>
      </div>
      <div class="col-6">
        <div class="card shadow-sm-custom">
          <div class="card-body text-center">
            <div class="h4 mb-0"><?php echo count(array_unique($produtos_sem_espacos ?? [])); ?></div>
            <div class="text-muted">Códigos únicos</div>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($produtos)): ?>
      <div class="mt-3 position-relative">
        <label for="codigosField" class="form-label">Códigos</label>
        <textarea id="codigosField" class="form-control" rows="6" readonly onclick="this.select()"><?php echo htmlspecialchars($codigos_concatenados); ?></textarea>
        <button class="btn btn-primary btn-sm mt-2 w-100" onclick="copiarCodigos()">
          <i class="bi bi-clipboard-check me-2"></i>
          Copiar para área de transferência
        </button>
        <div class="form-text">Clique no campo para selecionar tudo rapidamente.</div>
      </div>
    <?php else: ?>
      <div class="alert alert-warning mt-3 text-center">
        <strong>Nenhum produto disponível para etiquetas.</strong>
        <?php if (!empty($dependencia_selecionada)): ?>
          <div class="small">Não há produtos marcados ou cadastrados com código na dependência "<?php echo htmlspecialchars($dependencia_selecionada); ?>".</div>
        <?php else: ?>
          <div class="small">Marque produtos com o ícone de etiqueta 🏷️ ou cadastre produtos com código preenchido.</div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function copiarCodigos() {
  const codigosField = document.getElementById('codigosField');
  codigosField.select();
  codigosField.setSelectionRange(0, 99999);
  navigator.clipboard.writeText(codigosField.value).then(() => {
    const btn = document.activeElement;
  });
}
function filtrarPorDependencia() {
  const dependencia = document.getElementById('filtroDependencia').value;
  const url = new URL(window.location);
  if (dependencia) url.searchParams.set('dependencia', dependencia); else url.searchParams.delete('dependencia');
  window.location.href = url.toString();
}
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_copiar_etiquetas_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
$headerActions = '';
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
