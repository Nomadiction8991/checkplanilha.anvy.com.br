<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
require_once __DIR__ . '/../../../CRUD/READ/relatorio-14-1.php';

$pageTitle = 'Relatório 14.1';
$backUrl = '../shared/menu.php?id=' . urlencode($id_planilha);
$headerActions = '<button id="btnPrint" class="btn-header-action" title="Imprimir" onclick="validarEImprimir()"><i class="bi bi-printer"></i></button>';

// CSS customizado
$customCss = '
/* Formulário valores comuns */
.valores-comuns { 
    background: #f8f9fa; 
    padding: 15px; 
    border-radius: 8px; 
    margin-bottom: 15px;
    position: sticky;
    top: 0;
    z-index: 10;
}
.valores-comuns h6 { margin: 0 0 10px 0; font-size: 0.9rem; font-weight: 600; }
.form-grid { display: grid; grid-template-columns: 1fr; gap: 10px; }
.form-grid label { font-size: 0.875rem; font-weight: 500; margin-bottom: 4px; display: block; }
.form-grid input { width: 100%; padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 0.875rem; }

/* Container de páginas */
.paginas-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-bottom: 20px;
}

.pagina-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 15px;
    position: relative;
}

.pagina-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 2px solid #667eea;
}

.pagina-numero {
    font-weight: 600;
    color: #667eea;
    font-size: 1rem;
}

.pagina-info {
    font-size: 0.85rem;
    color: #666;
}

/* Wrapper da página A4 escalada */
.a4-viewport {
    width: 100%;
    overflow: hidden;
    background: #f8f9fa;
    border-radius: 4px;
    display: flex;
    justify-content: center;
    padding: 10px 0;
}

.a4-scaled {
    transform: scale(0.46);
    transform-origin: top center;
    width: 217%; /* 100% / 0.46 */
}

/* Campos editados ficam vermelhos */
.a4 input.editado,
.a4 textarea.editado {
    color: #dc3545 !important;
}

.a4 label:has(input[type="checkbox"].marcado) {
    color: #dc3545 !important;
}

@media print {
    .valores-comuns, .pagina-header { display: none !important; }
    
    .paginas-container {
        display: block;
        gap: 0;
    }
    
    .pagina-card {
        box-shadow: none;
        padding: 0;
        margin: 0;
        border-radius: 0;
        page-break-after: always;
    }
    
    .pagina-card:last-child {
        page-break-after: auto;
    }
    
    .a4-viewport {
        background: transparent;
        padding: 0;
        overflow: visible;
    }
    
    .a4-scaled {
        transform: none !important;
        width: 100% !important;
    }
    
    /* Cores voltam para preto */
    .a4 input.editado,
    .a4 textarea.editado,
    .a4 label:has(input[type="checkbox"].marcado) {
        color: #000 !important;
    }
}
';

ob_start();
?>

<?php if (count($produtos) > 0): ?>

<!-- Formulário de valores comuns -->
<div class="valores-comuns">
    <h6><i class="bi bi-ui-checks me-1"></i> Valores Comuns para Todos (<?php echo count($produtos); ?> páginas)</h6>
    <div class="form-grid">
        <div>
            <label>Administração</label>
            <input type="text" id="admin_geral" onchange="atualizarTodos('admin')">
        </div>
        <div>
            <label>Cidade</label>
            <input type="text" id="cidade_geral" onchange="atualizarTodos('cidade')">
        </div>
        <div>
            <label>Setor</label>
            <input type="text" id="setor_geral" onchange="atualizarTodos('setor')">
        </div>
        <div>
            <label>Administrador/Acessor</label>
            <input type="text" id="admin_acessor_geral" onchange="atualizarTodos('admin_acessor')">
        </div>
    </div>
</div>

<!-- Container de páginas -->
<div class="paginas-container">
    <?php foreach($produtos as $index => $row): ?>
        <div class="pagina-card">
            <div class="pagina-header">
                <span class="pagina-numero">
                    <i class="bi bi-file-earmark-text"></i> Página <?php echo $index + 1; ?> de <?php echo count($produtos); ?>
                </span>
                <span class="pagina-info"><?php echo htmlspecialchars($row['descricao_completa']); ?></span>
            </div>
            
            <div class="a4-viewport">
                <div class="a4-scaled">
                    <link rel="stylesheet" href="/dev/public/assets/css/relatorio-14-1.css">
                    <div class="a4">
                        <section class="cabecalho">
                            <table>
                                <tr class="row1">
                                    <th class="col1" rowspan="3">CCB</th>
                                    <th class="col2" rowspan="3">MANUAL ADMINISTRATIVO</th>
                                    <th class="col3">SEÇÃO: </th>
                                    <th class="col4">14</th>
                                </tr>
                                <tr class="row2">
                                    <th class="col3">FL./FLS. </th>
                                    <th class="col4">34/36</th>
                                </tr>
                                <tr class="row3">
                                    <th class="col3">DATA REVISÃO: </th>
                                    <th class="col4">24/09/2019</th>
                                </tr>
                                <tr class="row4">
                                    <th class="col1" rowspan="2">ASSUNTO</th>
                                    <th class="col2" rowspan="2">PATRIMÔNIO - BENS MÓVEIS</th>
                                    <th class="col3">EDIÇÃO: </th>
                                    <th class="col4">6</th>
                                </tr>
                                <tr class="row5">
                                    <th class="col3">REVISÃO: </th>
                                    <th class="col4">1</th>
                                </tr>
                            </table>
                        </section>
                        <section class="conteudo">
                            <h1>FORMULÁRIO 14.1: DECLARAÇÃO DE DOAÇÃO DE BEM MÓVEL</h1>
                            <div class="conteudo">
                                <table>
                                    <tr class="row1">
                                        <td class="col1" colspan="2">CONGREGAÇÃO CRISTÃ NO BRASIL</td>
                                        <td class="col2" colspan="2">FORMULÁRIO 14.1</td>
                                    </tr>
                                    <tr class="row2">
                                        <td class="col1" colspan="2">DECLARAÇÃO DE DOAÇÃO DE BENS MÓVEIS</td>
                                        <td class="col2" colspan="2">
                                            <label for="">Data Emissão</label><br>
                                            <input type="text" name="data_emissao" id="data_emissao_<?php echo $row['id']; ?>" value="<?php echo date('d/m/Y'); ?>" readonly>
                                        </td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row3">
                                        <td class="col1">A</td>
                                        <td class="col2" colspan="2">LOCALIDADE RECEBIDA</td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row4">
                                        <td class="col1">Administração</td>
                                        <td class="col2">Cidade</td>
                                        <td class="col3">Setor</td>
                                    </tr>
                                    <tr class="row5">
                                        <td class="col1">
                                            <input type="text" name="administracao" id="administracao_<?php echo $row['id']; ?>">
                                        </td>
                                        <td class="col2">
                                            <input type="text" name="cidade" id="cidade_<?php echo $row['id']; ?>">
                                        </td>
                                        <td class="col3">
                                            <input type="text" name="setor" id="setor_<?php echo $row['id']; ?>">
                                        </td>
                                    </tr>
                                    <tr class="row6">
                                        <td class="col1">CNPJ da Administração</td>
                                        <td class="col2">N° Relatório</td>
                                        <td class="col3">Casa de Oração</td>
                                    </tr>
                                    <tr class="row7">
                                        <td class="col1">
                                            <input type="text" name="cnpj" id="cnpj_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($cnpj_planilha ?? ''); ?>">
                                        </td>
                                        <td class="col2">
                                            <input type="text" name="numero_relatorio" id="numero_relatorio_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($numero_relatorio_auto ?? ''); ?>">
                                        </td>
                                        <td class="col3">
                                            <input type="text" name="casa_oracao" id="casa_oracao_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($casa_oracao_auto ?? ''); ?>">
                                        </td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row8">
                                        <td class="col1">B</td>
                                        <td class="col2" colspan="3">DESCRIÇÃO DO BEM</td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row9">
                                        <td class="col1" colspan="4">
                                            <textarea name="descricao_bem" id="descricao_bem_<?php echo $row['id']; ?>" readonly><?php echo htmlspecialchars($row['descricao_completa']); ?></textarea>
                                        </td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row10">
                                        <td class="col1">N° Nota fiscal</td>
                                        <td class="col2">Data de emissão</td>
                                        <td class="col3">Valor</td>
                                        <td class="col4">Fornecedor</td>
                                    </tr>
                                    <tr class="row11">
                                        <td class="col1">
                                            <input type="text" name="numero_nota" id="numero_nota_<?php echo $row['id']; ?>">
                                        </td>
                                        <td class="col2">
                                            <input type="text" name="data_emissao_nota" id="data_emissao_nota_<?php echo $row['id']; ?>">
                                        </td>
                                        <td class="col3">
                                            <input type="text" name="valor" id="valor_<?php echo $row['id']; ?>">
                                        </td>
                                        <td class="col4">
                                            <input type="text" name="fornecedor" id="fornecedor_<?php echo $row['id']; ?>">
                                        </td>
                                    </tr>
                                    <tr class="row12">
                                        <td class="col1" colspan="4">
                                            <p>Declaramos que estamos doando à CONGREGAÇÃO CRISTÃ NO BRASIL o bem acima descrito, de nossa propriedade, livre e sesembaraçado de dívidas e ônus, para uso na Casa de Oração acima identificada.</p><br>
                                            <label>
                                                <input type="checkbox" class="opcao-checkbox" name="opcao_1_<?php echo $row['id']; ?>" id="opcao_1_<?php echo $row['id']; ?>" data-page="<?php echo $index; ?>">
                                                O bem tem mais de cinco anos de uso e o documento fiscal de aquisição está anexo.
                                            </label><br>
                                            <label>
                                                <input type="checkbox" class="opcao-checkbox" name="opcao_2_<?php echo $row['id']; ?>" id="opcao_2_<?php echo $row['id']; ?>" data-page="<?php echo $index; ?>">
                                                O bem tem mais de cinco anos de uso, porém o documento fiscal de aquisição foi extraviado.
                                            </label><br>
                                            <label>
                                                <input type="checkbox" class="opcao-checkbox" name="opcao_3_<?php echo $row['id']; ?>" id="opcao_3_<?php echo $row['id']; ?>" data-page="<?php echo $index; ?>">
                                                O bem tem até cinco anos de uso e o documento fiscal de aquisição está anexo.
                                            </label><br><br>
                                            <p>Por ser verdade firmamos esta declaração.</p><br>
                                            <label>Local e data: <input type="text" name="local_data" id="local_data_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($comum_planilha); ?> ____/____/_______"></label>
                                        </td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row13">
                                        <td class="col1">C</td>
                                        <td class="col2" colspan="2">DOADOR</td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row14">
                                        <td class="col1"></td>
                                        <td class="col2">Dados do doador</td>
                                        <td class="col3">Dados do cônjuge</td>
                                    </tr>
                                    <tr class="row15">
                                        <td class="col1">Nome</td>
                                        <td class="col2"><input type="text" name="nome_doador" id="nome_doador_<?php echo $row['id']; ?>"></td>
                                        <td class="col3"><input type="text" name="nome_conjuge" id="nome_conjuge_<?php echo $row['id']; ?>"></td>
                                    </tr>
                                    <tr class="row16">
                                        <td class="col1">Endereço</td>
                                        <td class="col2"><input type="text" name="endereco_doador" id="endereco_doador_<?php echo $row['id']; ?>"></td>
                                        <td class="col3"><input type="text" name="endereco_conjuge" id="endereco_conjuge_<?php echo $row['id']; ?>"></td>
                                    </tr>
                                    <tr class="row17">
                                        <td class="col1">CPF</td>
                                        <td class="col2"><input type="text" name="cpf_doador" id="cpf_doador_<?php echo $row['id']; ?>"></td>
                                        <td class="col3"><input type="text" name="cpf_conjuge" id="cpf_conjuge_<?php echo $row['id']; ?>"></td>
                                    </tr>
                                    <tr class="row18">
                                        <td class="col1">RG</td>
                                        <td class="col2"><input type="text" name="rg_doador" id="rg_doador_<?php echo $row['id']; ?>"></td>
                                        <td class="col3"><input type="text" name="rg_conjuge" id="rg_conjuge_<?php echo $row['id']; ?>"></td>
                                    </tr>
                                    <tr class="row19">
                                        <td class="col1">Assinatura</td>
                                        <td class="col2"><input type="text" name="assinatura_doador" id="assinatura_doador_<?php echo $row['id']; ?>"></td>
                                        <td class="col3"><input type="text" name="assinatura_conjuge" id="assinatura_conjuge_<?php echo $row['id']; ?>"></td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row20">
                                        <td class="col1">D</td>
                                        <td class="col2" colspan="2">TERMO DE ACEITE DA DOAÇÃO</td>
                                    </tr>
                                </table>
                                <table>
                                    <tr class="row21">
                                        <td class="col1" colspan="3"><p>A Congregação Cristã No Brasil aceita a presente doação por atender necessidade do momento.</p></td>
                                    </tr>
                                    <tr class="row22">
                                        <td class="col1"></td>
                                        <td class="col2">Nome</td>
                                        <td class="col3">Assinatura</td>
                                    </tr>
                                    <tr class="row23">
                                        <td class="col1">Administrador/Acessor</td>
                                        <td class="col2"><input type="text" name="admin_acessor" id="admin_acessor_<?php echo $row['id']; ?>"></td>
                                        <td class="col3"><input type="text" name="assinatura_admin" id="assinatura_admin_<?php echo $row['id']; ?>"></td>
                                    </tr>
                                    <tr class="row24">
                                        <td class="col1">Doador</td>
                                        <td class="col2"></td>
                                        <td class="col3"></td>
                                    </tr>
                                </table>
                            </div>
                        </section>
                        <section class="rodape">
                            <table>
                                <tr class="row1">
                                    <td class="col1"></td>
                                    <td class="col2">sp.saopaulo.manualadm@congregacao.org.br</td>
                                    <td class="col3"></td>
                                </tr>
                            </table>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Nenhum produto encontrado para impressão do relatório 14.1.
</div>
<?php endif; ?>

<script>
// Armazenar valores iniciais dos campos
const valoresOriginais = new Map();

document.addEventListener('DOMContentLoaded', () => {
    inicializarDeteccaoEdicao();
});

// Detectar edição manual em inputs e textareas
function inicializarDeteccaoEdicao() {
    document.querySelectorAll('.a4 input[type="text"], .a4 textarea').forEach(campo => {
        valoresOriginais.set(campo.id, campo.value);
        
        campo.addEventListener('input', function() {
            const valorOriginal = valoresOriginais.get(this.id);
            if (this.value !== valorOriginal && this.value !== '') {
                this.classList.add('editado');
            } else {
                this.classList.remove('editado');
            }
        });
    });
    
    // Detectar checkboxes marcados
    document.querySelectorAll('.a4 input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                this.classList.add('marcado');
            } else {
                this.classList.remove('marcado');
            }
        });
    });
}

// Atualizar todos os campos
function atualizarTodos(tipo) {
    const valor = document.getElementById(tipo + '_geral').value;
    let selector;
    switch(tipo) {
        case 'admin': selector = '[id^="administracao_"]'; break;
        case 'cidade': selector = '[id^="cidade_"]'; break;
        case 'setor': selector = '[id^="setor_"]'; break;
        case 'admin_acessor': selector = '[id^="admin_acessor_"]'; break;
        default: selector = '[id^="' + tipo + '_"]';
    }
    const inputs = document.querySelectorAll(selector);
    inputs.forEach(input => {
        if (!input.id.includes('geral')) {
            input.value = valor;
            if (valor !== '') {
                input.classList.add('editado');
            }
        }
    });
}

// Apenas 1 checkbox por página
document.querySelectorAll('.opcao-checkbox').forEach(chk => {
    chk.addEventListener('change', () => {
        if (chk.checked) {
            const pageIndex = chk.dataset.page;
            document.querySelectorAll(`.opcao-checkbox[data-page="${pageIndex}"]`).forEach(other => {
                if (other !== chk) other.checked = false;
            });
        }
    });
});

// Validar e imprimir
function validarEImprimir() {
    const totalPaginas = document.querySelectorAll('.pagina-card').length;
    
    for (let i = 0; i < totalPaginas; i++) {
        const checks = document.querySelectorAll(`.opcao-checkbox[data-page="${i}"]`);
        const marcados = Array.from(checks).filter(c => c.checked).length;
        
        if (marcados !== 1) {
            alert(`Selecione exatamente 1 opção na página ${i + 1} antes de imprimir.`);
            // Rolar até a página com erro
            document.querySelectorAll('.pagina-card')[i].scrollIntoView({ behavior: 'smooth', block: 'start' });
            return false;
        }
    }
    
    window.print();
}
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_relatorio_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
