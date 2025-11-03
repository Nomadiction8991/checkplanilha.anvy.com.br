<?php
require_once __DIR__ . '/../../../CRUD/CREATE/importar-planilha.php';

// Configurações da página
$pageTitle = "Importar Planilha";
$backUrl = '../../../index.php';

// Iniciar buffer
ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- Arquivo CSV -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-file-earmark-arrow-up me-2"></i>
            Arquivo
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="arquivo" class="form-label">Arquivo CSV *</label>
                <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".csv" required>
                <div class="form-text">Selecione o arquivo CSV para importação</div>
            </div>
        </div>
    </div>

    <!-- Configurações Básicas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            Configurações Básicas
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="linhas_pular" class="form-label">Linhas iniciais a pular</label>
                <input type="number" class="form-control" id="linhas_pular" name="linhas_pular" 
                       value="<?php echo $_POST['linhas_pular'] ?? 25; ?>" min="0" required>
                <div class="form-text">Número de linhas do cabeçalho que devem ser ignoradas</div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="localizacao_comum" class="form-label">Célula Comum</label>
                    <input type="text" class="form-control" id="localizacao_comum" name="localizacao_comum" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_comum'] ?? 'D16'); ?>" 
                           required placeholder="Ex: D16">
                    <div class="form-text">Ex: D16</div>
                </div>

                <div class="col-md-6">
                    <label for="localizacao_data_posicao" class="form-label">Célula Data Posição</label>
                    <input type="text" class="form-control" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_data_posicao'] ?? 'D13'); ?>" 
                           required placeholder="Ex: D13">
                    <div class="form-text">Ex: D13</div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <label for="localizacao_endereco" class="form-label">Célula Endereço</label>
                    <input type="text" class="form-control" id="localizacao_endereco" name="localizacao_endereco" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_endereco'] ?? 'A4'); ?>" 
                           required placeholder="Ex: A4">
                    <div class="form-text">Ex: A4</div>
                </div>

                <div class="col-md-6">
                    <label for="localizacao_cnpj" class="form-label">Célula CNPJ</label>
                    <input type="text" class="form-control" id="localizacao_cnpj" name="localizacao_cnpj" 
                           value="<?php echo htmlspecialchars($_POST['localizacao_cnpj'] ?? 'U5'); ?>" 
                           required placeholder="Ex: U5">
                    <div class="form-text">Ex: U5</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapeamento de Colunas -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-columns-gap me-2"></i>
            Mapeamento de Colunas
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">Defina a letra da coluna para cada campo</p>
            
            <div class="row g-3">
                <div class="col-4">
                    <label for="codigo" class="form-label">Código</label>
                    <input type="text" class="form-control text-center fw-bold" name="codigo" 
                           value="<?php echo $_POST['codigo'] ?? 'A'; ?>" maxlength="2" required>
                </div>

                <div class="col-4">
                    <label for="nome" class="form-label">Nome</label>
                    <input type="text" class="form-control text-center fw-bold" name="nome" 
                           value="<?php echo $_POST['nome'] ?? 'D'; ?>" maxlength="2" required>
                </div>

                <div class="col-4">
                    <label for="dependencia" class="form-label">Dependência</label>
                    <input type="text" class="form-control text-center fw-bold" name="dependencia" 
                           value="<?php echo $_POST['dependencia'] ?? 'P'; ?>" maxlength="2" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Outros Dados -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-lines-fill me-2"></i>
            Outros Dados
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="administracao" class="form-label">Administração <span class="text-danger">*</span></label>
                    <select id="administracao" name="administracao" class="form-select" required>
                        <option value="">Carregando...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                    <select id="cidade" name="cidade" class="form-select" required disabled>
                        <option value="">Carregando...</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="setor" class="form-label">Setor</label>
                    <input type="number" class="form-control" id="setor" name="setor" 
                           value="<?php echo htmlspecialchars($_POST['setor'] ?? ''); ?>" min="0" step="1">
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-upload me-2"></i>
        Importar Planilha
    </button>
</form>

<?php
$contentHtml = ob_get_clean();
// Injetar script para captura de assinaturas (desenho em canvas -> hidden input)
$pre_administracao = json_encode($_POST['administracao'] ?? '');
$pre_cidade = json_encode($_POST['cidade'] ?? '');

$script = <<<HTML
<script>
// Antes do submit, inicializações e IBGE (sem assinatura)
document.addEventListener('DOMContentLoaded', function(){
    // Validação simples: Administração e Cidade obrigatórios
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){
        const estado = document.getElementById('administracao').value;
        const cidade = document.getElementById('cidade').value;
        if (!estado || !cidade) {
            e.preventDefault();
            alert('Por favor preencha Administração e Cidade (campos obrigatórios).');
            return false;
        }
    });

    // Popula selects de estados e cidades via IBGE
    async function loadEstados(){
        const sel = document.getElementById('administracao');
        sel.innerHTML = '<option value="">Carregando estados...</option>';
        try{
            const res = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados');
            const estados = await res.json();
            // encontrar MT apenas
            const mt = estados.find(s => s.sigla === 'MT');
            if(!mt){ sel.innerHTML = '<option value="">MT não encontrado</option>'; return; }
            // Em vez de popular administracao com o estado, vamos carregar as cidades de MT
            // e popular tanto `administracao` quanto `cidade` com a lista no formato "MT - Cidade".
            sel.innerHTML = '<option value="">Carregando cidades de MT...</option>';
            await loadCidades(mt.id);
        } catch(err){
            sel.innerHTML = '<option value="">Erro ao carregar estados</option>';
            console.error(err);
        }
    }
    async function loadCidades(estadoId){
        const cidadeSel = document.getElementById('cidade');
        const adminSel = document.getElementById('administracao');
        cidadeSel.innerHTML = '<option value="">Carregando cidades...</option>';
        cidadeSel.disabled = true;
        adminSel.innerHTML = '<option value="">Carregando cidades...</option>';
        adminSel.disabled = true;
        try{
            const res = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados/'+estadoId+'/municipios');
            const cidades = await res.json();
            cidades.sort((a,b)=>a.nome.localeCompare(b.nome));
            cidadeSel.innerHTML = '<option value="">Selecione a cidade</option>';
            adminSel.innerHTML = '<option value="">Selecione a cidade</option>';
            const sigla = 'MT';
            cidades.forEach(ct => {
                const val = sigla + ' - ' + ct.nome;
                const opt = document.createElement('option'); opt.value = val; opt.text = val; cidadeSel.appendChild(opt);
                const opt2 = document.createElement('option'); opt2.value = val; opt2.text = val; adminSel.appendChild(opt2);
            });
            cidadeSel.disabled = false;
            adminSel.disabled = false;
            const pre = {$pre_cidade};
            const preA = {$pre_administracao};
            if (pre) { for(const o of cidadeSel.options) if (o.value===pre) { o.selected=true; break; } }
            if (preA) { for(const o of adminSel.options) if (o.value===preA) { o.selected=true; break; } }
        } catch(err){
            cidadeSel.innerHTML = '<option value="">Erro ao carregar cidades</option>';
            console.error(err);
        }
    }
    // Os selects `administracao` e `cidade` foram preenchidos com a mesma lista de
    // cidades de MT, mas são independentes — não há escuta de sincronização entre eles.

    // inicialização com pré-seleção (se necessário)
    (async function(){
        await loadEstados();
    })();
});
</script>
HTML;

$contentHtml = $contentHtml . $script;
$tempFile = __DIR__ . '/../../../temp_importar_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
