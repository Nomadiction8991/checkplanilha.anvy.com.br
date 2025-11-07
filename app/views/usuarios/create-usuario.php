<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
include __DIR__ . '/../../../CRUD/CREATE/usuario.php';

$pageTitle = 'Novo Usuário';
$backUrl = './read-usuario.php';

ob_start();
?>

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- jQuery e InputMask -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/inputmask@5.0.8/dist/inputmask.min.js"></script>
<!-- SignaturePad -->
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>

<style>
.signature-canvas {
    border: 2px solid #dee2e6;
    border-radius: 0.375rem;
    background: white;
    cursor: crosshair;
    touch-action: none;
}
.signature-preview {
    max-width: 100%;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background: #f8f9fa;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<form method="POST" id="formUsuario">
    <!-- Campo oculto: tipo de usuário -->
    <input type="hidden" name="tipo" value="Administrador/Acessor">
    
    <!-- Card 1: Dados Básicos -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-person-plus me-2"></i>
            Dados Básicos
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label for="nome" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nome" name="nome" 
                       value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="cpf" name="cpf" 
                           value="<?php echo htmlspecialchars($_POST['cpf'] ?? ''); ?>" 
                           placeholder="000.000.000-00" required>
                </div>
                <div class="col-md-6">
                    <label for="telefone" class="form-label">Telefone <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="telefone" name="telefone" 
                           value="<?php echo htmlspecialchars($_POST['telefone'] ?? ''); ?>" 
                           placeholder="(00) 00000-0000" required>
                </div>
            </div>

            <div class="mb-3 mt-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="senha" class="form-label">Senha <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="senha" name="senha" 
                           minlength="6" required>
                    <small class="text-muted">Mínimo de 6 caracteres</small>
                </div>

                <div class="col-md-6">
                    <label for="confirmar_senha" class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" 
                           minlength="6" required>
                </div>
            </div>

            <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" 
                       <?php echo (isset($_POST['ativo']) || !isset($_POST['nome'])) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="ativo">
                    Usuário Ativo
                </label>
            </div>
        </div>
    </div>

    <!-- Card 2: Endereço -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-geo-alt me-2"></i>
            Endereço
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="cep" class="form-label">CEP</label>
                    <input type="text" class="form-control" id="cep" name="endereco_cep" 
                           value="<?php echo htmlspecialchars($_POST['endereco_cep'] ?? ''); ?>" 
                           placeholder="00000-000">
                    <small class="text-muted">Preencha para buscar automaticamente</small>
                </div>
                <div class="col-md-8">
                    <label for="logradouro" class="form-label">Logradouro</label>
                    <input type="text" class="form-control" id="logradouro" name="endereco_logradouro" 
                           value="<?php echo htmlspecialchars($_POST['endereco_logradouro'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-3">
                    <label for="numero" class="form-label">Número</label>
                    <input type="text" class="form-control" id="numero" name="endereco_numero" 
                           value="<?php echo htmlspecialchars($_POST['endereco_numero'] ?? ''); ?>">
                </div>
                <div class="col-md-5">
                    <label for="complemento" class="form-label">Complemento</label>
                    <input type="text" class="form-control" id="complemento" name="endereco_complemento" 
                           value="<?php echo htmlspecialchars($_POST['endereco_complemento'] ?? ''); ?>" 
                           placeholder="Apto, bloco, etc">
                </div>
                <div class="col-md-4">
                    <label for="bairro" class="form-label">Bairro</label>
                    <input type="text" class="form-control" id="bairro" name="endereco_bairro" 
                           value="<?php echo htmlspecialchars($_POST['endereco_bairro'] ?? ''); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-8">
                    <label for="cidade" class="form-label">Cidade</label>
                    <input type="text" class="form-control" id="cidade" name="endereco_cidade" 
                           value="<?php echo htmlspecialchars($_POST['endereco_cidade'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label for="estado" class="form-label">Estado</label>
                    <select class="form-select" id="estado" name="endereco_estado">
                        <option value="">Selecione</option>
                        <option value="AC" <?php echo ($_POST['endereco_estado'] ?? '') === 'AC' ? 'selected' : ''; ?>>Acre</option>
                        <option value="AL" <?php echo ($_POST['endereco_estado'] ?? '') === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                        <option value="AP" <?php echo ($_POST['endereco_estado'] ?? '') === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                        <option value="AM" <?php echo ($_POST['endereco_estado'] ?? '') === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                        <option value="BA" <?php echo ($_POST['endereco_estado'] ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                        <option value="CE" <?php echo ($_POST['endereco_estado'] ?? '') === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                        <option value="DF" <?php echo ($_POST['endereco_estado'] ?? '') === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                        <option value="ES" <?php echo ($_POST['endereco_estado'] ?? '') === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                        <option value="GO" <?php echo ($_POST['endereco_estado'] ?? '') === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                        <option value="MA" <?php echo ($_POST['endereco_estado'] ?? '') === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                        <option value="MT" <?php echo ($_POST['endereco_estado'] ?? '') === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                        <option value="MS" <?php echo ($_POST['endereco_estado'] ?? '') === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                        <option value="MG" <?php echo ($_POST['endereco_estado'] ?? '') === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                        <option value="PA" <?php echo ($_POST['endereco_estado'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pará</option>
                        <option value="PB" <?php echo ($_POST['endereco_estado'] ?? '') === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                        <option value="PR" <?php echo ($_POST['endereco_estado'] ?? '') === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                        <option value="PE" <?php echo ($_POST['endereco_estado'] ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                        <option value="PI" <?php echo ($_POST['endereco_estado'] ?? '') === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                        <option value="RJ" <?php echo ($_POST['endereco_estado'] ?? '') === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                        <option value="RN" <?php echo ($_POST['endereco_estado'] ?? '') === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                        <option value="RS" <?php echo ($_POST['endereco_estado'] ?? '') === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                        <option value="RO" <?php echo ($_POST['endereco_estado'] ?? '') === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                        <option value="RR" <?php echo ($_POST['endereco_estado'] ?? '') === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                        <option value="SC" <?php echo ($_POST['endereco_estado'] ?? '') === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                        <option value="SP" <?php echo ($_POST['endereco_estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                        <option value="SE" <?php echo ($_POST['endereco_estado'] ?? '') === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                        <option value="TO" <?php echo ($_POST['endereco_estado'] ?? '') === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Assinatura Digital -->
    <div class="card mb-3">
        <div class="card-header">
            <i class="bi bi-pen me-2"></i>
            Assinatura Digital
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <i class="bi bi-info-circle me-1"></i>
                Desenhe sua assinatura no campo abaixo. Use mouse, touch ou caneta digital.
            </p>
            
            <div class="mb-3">
                <canvas id="signatureCanvas" class="signature-canvas" width="600" height="200"></canvas>
            </div>
            
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-danger" id="btnLimparAssinatura">
                    <i class="bi bi-eraser me-1"></i>
                    Limpar
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDesfazer">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                    Desfazer
                </button>
            </div>
            
            <!-- Campo hidden para armazenar assinatura em base64 -->
            <input type="hidden" id="assinatura" name="assinatura">
        </div>
    </div>

    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            Cadastrar Usuário
        </button>
    </div>
</form>

<script>
// ========== MÁSCARAS COM INPUTMASK ==========
$(document).ready(function() {
    // Máscara CPF: 000.000.000-00
    Inputmask('999.999.999-99').mask('#cpf');
    
    // Máscara Telefone: (00) 00000-0000 ou (00) 0000-0000
    Inputmask(['(99) 99999-9999', '(99) 9999-9999']).mask('#telefone');
    
    // Máscara CEP: 00000-000
    Inputmask('99999-999').mask('#cep');
});

// ========== VIACEP: BUSCA AUTOMÁTICA DE ENDEREÇO ==========
document.getElementById('cep').addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');
    
    if (cep.length !== 8) return;
    
    // Limpar campos antes de buscar
    document.getElementById('logradouro').value = 'Buscando...';
    document.getElementById('bairro').value = '';
    document.getElementById('cidade').value = '';
    document.getElementById('estado').value = '';
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (data.erro) {
                alert('CEP não encontrado!');
                document.getElementById('logradouro').value = '';
                return;
            }
            
            document.getElementById('logradouro').value = data.logradouro || '';
            document.getElementById('bairro').value = data.bairro || '';
            document.getElementById('cidade').value = data.localidade || '';
            document.getElementById('estado').value = data.uf || '';
            
            // Focar no número após preencher
            document.getElementById('numero').focus();
        })
        .catch(error => {
            console.error('Erro ao buscar CEP:', error);
            alert('Erro ao buscar CEP. Tente novamente.');
            document.getElementById('logradouro').value = '';
        });
});

// ========== ASSINATURA DIGITAL COM SIGNATUREPAD ==========
const canvas = document.getElementById('signatureCanvas');
const signaturePad = new SignaturePad(canvas, {
    backgroundColor: 'rgb(255, 255, 255)',
    penColor: 'rgb(0, 0, 0)',
    minWidth: 1,
    maxWidth: 2.5
});

// Ajustar canvas para alta resolução (Retina/HiDPI)
function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const width = canvas.offsetWidth;
    const height = canvas.offsetHeight;
    
    canvas.width = width * ratio;
    canvas.height = height * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    
    signaturePad.clear(); // Limpa ao redimensionar
}

// Redimensionar ao carregar e ao mudar tamanho da janela
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

// Botão Limpar
document.getElementById('btnLimparAssinatura').addEventListener('click', function() {
    signaturePad.clear();
    document.getElementById('assinatura').value = '';
});

// Botão Desfazer
document.getElementById('btnDesfazer').addEventListener('click', function() {
    const data = signaturePad.toData();
    if (data && data.length > 0) {
        data.pop(); // Remove última linha
        signaturePad.fromData(data);
    }
});

// ========== VALIDAÇÃO E ENVIO DO FORMULÁRIO ==========
document.getElementById('formUsuario').addEventListener('submit', function(e) {
    const senha = document.getElementById('senha').value;
    const confirmar = document.getElementById('confirmar_senha').value;
    
    // Validar senhas
    if (senha !== confirmar) {
        e.preventDefault();
        alert('As senhas não conferem!');
        return false;
    }
    
    // Salvar assinatura em base64 no campo hidden
    if (!signaturePad.isEmpty()) {
        const assinaturaBase64 = signaturePad.toDataURL('image/png');
        document.getElementById('assinatura').value = assinaturaBase64;
    } else {
        // Assinatura é opcional, mas pode avisar
        if (!confirm('Você não adicionou uma assinatura. Deseja continuar mesmo assim?')) {
            e.preventDefault();
            return false;
        }
    }
});
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_create_usuario_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
