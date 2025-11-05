<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/UPDATE/editar-planilha.php';

$id_planilha = $_GET['id'] ?? null;

$pageTitle = "Editar Planilha";
$backUrl = '../../../index.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <!-- Info Atual -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-info-circle me-2"></i>
            Informações Atuais
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">CNPJ</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['cnpj'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Comum</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['comum'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label for="endereco" class="form-label">Endereço</label>
                    <input type="text" class="form-control" id="endereco" name="endereco" 
                           value="<?php echo htmlspecialchars($planilha['endereco'] ?? ''); ?>" disabled>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data Posição</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($planilha['data_posicao'] ?? ''); ?>" disabled>
                </div>
            </div>
            
            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo ($planilha['ativo'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ativo">
                    Planilha Ativa
                </label>
            </div>
        </div>
    </div>

    <!-- Configurações -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-gear me-2"></i>
            Configurações de Importação
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="linhas_pular" class="form-label">Linhas Iniciais a Pular <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="linhas_pular" name="linhas_pular" 
                       value="<?php echo $config['pulo_linhas'] ?? 25; ?>" min="0" required>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="localizacao_cnpj" class="form-label">CNPJ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="localizacao_cnpj" name="localizacao_cnpj" 
                           value="<?php echo htmlspecialchars($config['cnpj'] ?? 'U5'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_comum" class="form-label">Comum <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="localizacao_comum" name="localizacao_comum" 
                           value="<?php echo htmlspecialchars($config['comum'] ?? 'D16'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_endereco" class="form-label">Endereço <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="localizacao_endereco" name="localizacao_endereco" 
                           value="<?php echo htmlspecialchars($config['endereco'] ?? 'A4'); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="localizacao_data_posicao" class="form-label">Data Posição <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="localizacao_data_posicao" name="localizacao_data_posicao" 
                           value="<?php echo htmlspecialchars($config['data_posicao'] ?? 'D13'); ?>" required>
                </div>
            </div>
        </div>
    </div>

    <!-- Atualizar Dados -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-arrow-repeat me-2"></i>
            Atualizar Dados
        </div>
        <div class="card-body">
            <label for="arquivo" class="form-label">Novo Arquivo CSV (opcional)</label>
            <input type="file" class="form-control" id="arquivo" name="arquivo" accept=".csv">
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
$pre_administracao = json_encode($planilha['administracao'] ?? '');
$pre_cidade = json_encode($planilha['cidade'] ?? '');

$script = <<<HTML
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Estados / Cidades (popula cidades de MT e armazena valores no formato "MT - Cidade")
    const preAdministracao = $pre_administracao;
    const preCidade = $pre_cidade;

    async function loadEstados(){
        const sel = document.getElementById('administracao');
        sel.innerHTML = '<option value="">Carregando estados...</option>';
        try{
            const res = await fetch('https://servicodados.ibge.gov.br/api/v1/localidades/estados');
            const estados = await res.json();
            // encontrar MT
            const mt = estados.find(s => s.sigla === 'MT');
            if(!mt){ sel.innerHTML='<option value="">MT não encontrado</option>'; return; }
            sel.innerHTML = '<option value="">Carregando cidades de MT...</option>';
            await loadCidades(mt.id);
        }catch(err){ sel.innerHTML='<option value="">Erro ao carregar estados</option>'; console.error(err); }
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
            cidades.forEach(ct=>{
                const val = sigla + ' - ' + ct.nome;
                const opt = document.createElement('option'); opt.value = val; opt.text = val; cidadeSel.appendChild(opt);
                const opt2 = document.createElement('option'); opt2.value = val; opt2.text = val; adminSel.appendChild(opt2);
            });
            cidadeSel.disabled = false; adminSel.disabled = false;
            if(preCidade){ for(const o of cidadeSel.options) if(o.value===preCidade){ o.selected=true; break; } }
            if(preAdministracao){ for(const o of adminSel.options) if(o.value===preAdministracao){ o.selected=true; break; } }
        }catch(err){ cidadeSel.innerHTML='<option value="">Erro ao carregar cidades</option>'; console.error(err); }
    }

    // Validação do formulário
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e){ 
        const estado = document.getElementById('administracao').value; 
        const cidadeVal = document.getElementById('cidade').value; 
        if(!estado || !cidadeVal){ 
            e.preventDefault(); 
            alert('Por favor preencha Administração e Cidade (campos obrigatórios).'); 
            return false; 
        }
    });

    // Inicialização: carregar estados, depois cidades e aplicar seleção pré-existente se houver
    loadEstados();
});
</script>
HTML;

$contentHtml = $contentHtml . $script;
$tempFile = __DIR__ . '/../../../temp_editar_planilha_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
