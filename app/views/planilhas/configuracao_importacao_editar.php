<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';


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
            throw new Exception('AdministraÃƒÂ§ÃƒÂ£o e Cidade sÃƒÂ£o obrigatÃƒÂ³rios.');
        }

        // Obter comum_id pela planilha
        $planilha_atual = carregar_planilha($conexao, $id_planilha);
        if (!$planilha_atual) {
            throw new Exception('Planilha nÃƒÂ£o encontrada.');
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

// Dados atualizados para exibiÃƒÂ§ÃƒÂ£o
$planilha = carregar_planilha($conexao, $id_planilha);
if (!$planilha) { 
    die('Planilha nÃƒÂ£o encontrada.');
}

$pageTitle = 'Editar Planilha';
$backUrl = '../comuns/configuracoes_importacao.php?comum_id=' . urlencode((string)$planilha['comum_id']);

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST">
    <!-- Card: Dados do Comum -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-building me-2"></i>
            Dados do Comum
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="administracao" class="form-label">AdministraÃƒÂ§ÃƒÂ£o <span class="text-danger">*</span></label>
                    <?php 
                        // AdministraÃƒÂ§ÃƒÂ£o e cidade armazenadas como 'MT - NomeDaCidade'
                        $administracao_atual = $planilha['administracao'] ?? '';
                        $cidade_atual = $planilha['cidade'] ?? '';
                        // Extrair somente nome apÃƒÂ³s ' - '
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
                            'CuiabÃƒÂ¡','VÃƒÂ¡rzea Grande','RondonÃƒÂ³polis','Sinop','Sorriso','Barra do GarÃƒÂ§as','TangarÃƒÂ¡ da Serra','Lucas do Rio Verde','Primavera do Leste','Alta Floresta','Campo Verde','CÃƒÂ¡ceres','ColÃƒÂ­der','GuarantÃƒÂ£ do Norte','JuÃƒÂ­na','Mirassol dÃ¢â‚¬â„¢Oeste','Nova Mutum','Pontes e Lacerda','SÃƒÂ£o FÃƒÂ©lix do Araguaia','Peixoto de Azevedo'
                        ];
                        sort($cidades_mt);
                    ?>
                    <select id="administracao" name="administracao" class="form-select" required>
                        <?php if ($administracao_atual !== ''): ?>
                            <option value="<?php echo htmlspecialchars($administracao_atual); ?>" selected><?php echo htmlspecialchars($administracao_atual); ?></option>
                        <?php else: ?>
                            <option value="" selected>Selecione...</option>
                        <?php endif; ?>
                        <?php foreach ($cidades_mt as $c): 
                            $val = 'MT - ' . $c;
                            if (strcasecmp($administracao_atual, $val) === 0) { continue; }
                        ?>
                            <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($val); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                    <select id="cidade" name="cidade" class="form-select" required>
                        <?php if ($cidade_atual !== ''): ?>
                            <option value="<?php echo htmlspecialchars($cidade_atual); ?>" selected><?php echo htmlspecialchars($cidade_atual); ?></option>
                        <?php else: ?>
                            <option value="" selected>Selecione...</option>
                        <?php endif; ?>
                        <?php foreach ($cidades_mt as $c): 
                            $val = 'MT - ' . $c;
                            if (strcasecmp($cidade_atual, $val) === 0) { continue; }
                        ?>
                            <option value="<?php echo htmlspecialchars($val); ?>"><?php echo htmlspecialchars($val); ?></option>
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

    <!-- Card: Dados da Planilha (apenas ativaÃƒÂ§ÃƒÂ£o) -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            Dados da Planilha
        </div>
        <div class="card-body">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo ($planilha['ativo'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ativo">
                    Planilha Ativa
                </label>
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
// Reutiliza a mesma lÃƒÂ³gica do importar-planilha para modal, estados e cidades
// Pre-encode any server values used by the script to avoid parsing issues
// Render direto sem JS (prefill jÃƒÂ¡ feito via PHP)
$contentHtmlFinal = $contentHtml;
$tempFile = __DIR__ . '/../../../temp_editar_planilha_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtmlFinal);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app_wrapper.php';
unlink($tempFile);
?>


