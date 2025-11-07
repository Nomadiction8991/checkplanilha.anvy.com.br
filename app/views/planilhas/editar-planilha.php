<?php
require_once __DIR__ . '/../../../auth.php';
require_once __DIR__ . '/../../../CRUD/conexao.php';

$id_planilha = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_planilha <= 0) {
    header('Location: ../../../index.php');
    exit;
}

$mensagem = '';
$tipo_mensagem = '';

// Carregar dados da planilha e do comum associado
function carregar_planilha($conexao, $id_planilha) {
    $sql = "SELECT p.id, p.comum_id, p.ativo, p.data_posicao,
                   c.descricao AS comum_descricao,
                   c.administracao, c.cidade, c.setor
            FROM planilhas p
            LEFT JOIN comums c ON c.id = p.comum_id
            WHERE p.id = :id";
    $st = $conexao->prepare($sql);
    $st->bindValue(':id', $id_planilha, PDO::PARAM_INT);
    $st->execute();
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $administracao = trim($_POST['administracao'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $setor = isset($_POST['setor']) && $_POST['setor'] !== '' ? (int)$_POST['setor'] : null;

        if ($administracao === '' || $cidade === '') {
            throw new Exception('Administração e Cidade são obrigatórios.');
        }

        // Obter comum_id pela planilha
        $planilha_atual = carregar_planilha($conexao, $id_planilha);
        if (!$planilha_atual) {
            throw new Exception('Planilha não encontrada.');
        }

        $conexao->beginTransaction();

        // Atualizar status da planilha
        $up1 = $conexao->prepare('UPDATE planilhas SET ativo = :ativo WHERE id = :id');
        $up1->bindValue(':ativo', $ativo, PDO::PARAM_INT);
        $up1->bindValue(':id', $id_planilha, PDO::PARAM_INT);
        $up1->execute();

        // Atualizar dados do comum relacionado
        $up2 = $conexao->prepare('UPDATE comums SET administracao = :adm, cidade = :cid, setor = :setor WHERE id = :cid_comum');
        $up2->bindValue(':adm', $administracao);
        $up2->bindValue(':cid', $cidade);
        if ($setor === null) {
            $up2->bindValue(':setor', null, PDO::PARAM_NULL);
        } else {
            $up2->bindValue(':setor', $setor, PDO::PARAM_INT);
        }
        $up2->bindValue(':cid_comum', (int)$planilha_atual['comum_id'], PDO::PARAM_INT);
        $up2->execute();

        $conexao->commit();

        $mensagem = 'Dados atualizados com sucesso!';
        $tipo_mensagem = 'success';
    } catch (Exception $e) {
        if ($conexao->inTransaction()) { $conexao->rollBack(); }
        $mensagem = 'Erro ao atualizar: ' . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}

// Dados atualizados para exibição
$planilha = carregar_planilha($conexao, $id_planilha);
if (!$planilha) { 
    die('Planilha não encontrada.');
}

$pageTitle = 'Editar Planilha';
$backUrl = '../comuns/listar-planilhas.php?comum_id=' . urlencode((string)$planilha['comum_id']);

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            Dados da Planilha
        </div>
        <div class="card-body">
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo ($planilha['ativo'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ativo">
                    Planilha Ativa
                </label>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="administracao" class="form-label">Administração <span class="text-danger">*</span></label>
                    <?php 
                        // Administração e cidade armazenadas como 'MT - NomeDaCidade'
                        $administracao_atual = $planilha['administracao'] ?? '';
                        $cidade_atual = $planilha['cidade'] ?? '';
                        // Extrair somente nome após ' - '
                        $nome_cidade_adm = '';
                        if (strpos($administracao_atual,' - ') !== false) {
                            $parts = explode(' - ',$administracao_atual,2);
                            $nome_cidade_adm = $parts[1];
                        }
                        $nome_cidade_cid = '';
                        if (strpos($cidade_atual,' - ') !== false) {
                            $parts2 = explode(' - ',$cidade_atual,2);
                            $nome_cidade_cid = $parts2[1];
                        }
                        // Definir base de cidades de MT (pode ser ampliado depois ou carregado de tabela auxiliar)
                        $cidades_mt = [
                            'Cuiabá','Várzea Grande','Rondonópolis','Sinop','Sorriso','Barra do Garças','Tangará da Serra','Lucas do Rio Verde','Primavera do Leste','Alta Floresta','Campo Verde','Cáceres','Colíder','Guarantã do Norte','Juína','Mirassol d’Oeste','Nova Mutum','Pontes e Lacerda','São Félix do Araguaia','Peixoto de Azevedo'
                        ];
                        sort($cidades_mt);
                    ?>
                    <select id="administracao" name="administracao" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($cidades_mt as $c): 
                            $val = 'MT - ' . $c;
                            $sel = ($administracao_atual === $val) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($val); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                    <select id="cidade" name="cidade" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($cidades_mt as $c): 
                            $val = 'MT - ' . $c;
                            $sel = ($cidade_atual === $val) ? 'selected' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($val); ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($val); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="setor" class="form-label">Setor</label>
                    <input type="number" class="form-control" id="setor" name="setor" 
                           value="<?php echo htmlspecialchars($planilha['setor'] ?? ''); ?>" min="0" step="1">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-check-lg me-2"></i>
        Atualizar Planilha
    </button>
</form>

<?php
$contentHtml = ob_get_clean();

// Script para captura de assinaturas e carregar assinaturas existentes
// Reutiliza a mesma lógica do importar-planilha para modal, estados e cidades
// Pre-encode any server values used by the script to avoid parsing issues
// Render direto sem JS (prefill já feito via PHP)
$contentHtmlFinal = $contentHtml;
$tempFile = __DIR__ . '/../../../temp_editar_planilha_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtmlFinal);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
