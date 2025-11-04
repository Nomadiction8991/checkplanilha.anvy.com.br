<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário 14.1 - Template</title>
    <!-- CSS externo removido para evitar interferência; estilos devem vir do HTML/inline quando necessário -->
</head>
<body>

<!-- 
    TEMPLATE DO RELATÓRIO 14.1
    
    Variáveis disponíveis para preenchimento:
    {{CNPJ}} - CNPJ da planilha
    {{NUMERO_RELATORIO}} - Número do relatório
    {{CASA_ORACAO}} - Nome da casa de oração
    {{PRODUTOS}} - Array de produtos com:
        - codigo: Código do produto
        - descricao: Descrição do produto
        - obs: Observações
        
    Cada produto gera uma página A4 completa
-->

<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticação
// Se $produtos não foi definido, usar array vazio
$produtos = $produtos ?? [];
$cnpj = $cnpj ?? '{{CNPJ}}';
$numero_relatorio = $numero_relatorio ?? '{{NUMERO_RELATORIO}}';
$casa_oracao = $casa_oracao ?? '{{CASA_ORACAO}}';

// Se não houver produtos, criar uma página em branco como exemplo
if (empty($produtos)) {
    $produtos = [
        [
            'codigo' => '',
            'descricao' => '',
            'obs' => ''
        ]
    ];
}

// Contador de páginas
$total_paginas = count($produtos);
$pagina_atual = 0;

foreach ($produtos as $produto):
    $pagina_atual++;
?>

<div class="a4">
    <!-- CABEÇALHO -->
    <section class="cabecalho">
        <table>
            <tr class="row1">
                <th class="col1">CCB</th>
                <th class="col2">MANUAL ADMINISTRATIVO</th>
                <th class="col3">Nº FORM</th>
                <th class="col4">14.1</th>
            </tr>
            <tr class="row2">
                <th class="col1">ASSUNTO</th>
                <th class="col2">PATRIMÔNIO - BENS MÓVEIS</th>
                <th class="col3">VALIDADE</th>
                <th class="col4"></th>
            </tr>
            <tr class="row3">
                <th class="col1">APLICAÇÃO</th>
                <th class="col2">Setores Administrativos das Regionais, Casas de Oração e Anexos</th>
                <th class="col3">APROVAÇÃO</th>
                <th class="col4"></th>
            </tr>
            <tr class="row4">
                <th class="col1">TITULO</th>
                <th class="col2">FORMULÁRIO PARA INVENTÁRIO FÍSICO DE BENS PATRIMONIAIS</th>
                <th class="col3">REVISÃO</th>
                <th class="col4"></th>
            </tr>
        </table>
    </section>

    <!-- CONTEÚDO -->
    <section class="conteudo">
        <h1>TERMO DE RESPONSABILIDADE E CONTROLE DE BENS PATRIMONIAIS</h1>
        
        <div class="conteudo">
            <table>
                <!-- Linha 1: CNPJ -->
                <tr class="row1">
                    <td class="col1">01 CNPJ (Raiz com 08 dígitos)</td>
                    <td class="col2">
                        <input type="text" value="<?php echo htmlspecialchars($cnpj); ?>" data-field="cnpj">
                    </td>
                </tr>

                <!-- Linha 2: Número do Relatório -->
                <tr class="row2">
                    <td class="col1">
                        <input type="text" value="<?php echo htmlspecialchars($numero_relatorio); ?>" data-field="numero_relatorio">
                    </td>
                    <td class="col2">02 Nº Relatório</td>
                </tr>

                <!-- Linha 3: Identificação -->
                <tr class="row3">
                    <td class="col1">A</td>
                    <td class="col2">IDENTIFICAÇÃO</td>
                </tr>

                <!-- Linha 4: Rótulos Tipo/Regional/Comum -->
                <tr class="row4">
                    <td class="col1">03 Tipo (Reg, CO, Anx)</td>
                    <td class="col2">04 Regional</td>
                    <td class="col3">05 Comum</td>
                </tr>

                <!-- Linha 5: Campos Tipo/Regional/Comum -->
                <tr class="row5">
                    <td class="col1"><input type="text" value="" data-field="tipo"></td>
                    <td class="col2"><input type="text" value="" data-field="regional"></td>
                    <td class="col3"><input type="text" value="" data-field="comum"></td>
                </tr>

                <!-- Linha 6: Rótulos Nome da Casa -->
                <tr class="row6">
                    <td class="col1" colspan="3">06 Nome da Casa de Oração</td>
                </tr>

                <!-- Linha 7: Campo Nome da Casa -->
                <tr class="row7">
                    <td class="col1" colspan="3">
                        <input type="text" value="<?php echo htmlspecialchars($casa_oracao); ?>" data-field="casa_oracao">
                    </td>
                </tr>

                <!-- Linha 8: Identificação do Bem -->
                <tr class="row8">
                    <td class="col1">B</td>
                    <td class="col2">IDENTIFICAÇÃO DO BEM PATRIMONIAL</td>
                </tr>

                <!-- Linha 9: Código e Descrição -->
                <tr class="row9">
                    <td colspan="3">
                        <strong>07 Código:</strong> 
                        <input type="text" value="<?php echo htmlspecialchars($produto['codigo'] ?? ''); ?>" data-field="codigo" style="width: 30%;">
                        &nbsp;&nbsp;&nbsp;
                        <strong>08 Descrição:</strong> 
                        <input type="text" value="<?php echo htmlspecialchars($produto['descricao'] ?? ''); ?>" data-field="descricao" style="width: 50%;">
                    </td>
                </tr>

                <!-- Linha 10: Rótulos Marca/Modelo/NS -->
                <tr class="row10">
                    <td class="col1">09 Marca</td>
                    <td class="col2">10 Modelo</td>
                    <td class="col3">11 Nº Série</td>
                    <td class="col4">12 Ano Fabric.</td>
                </tr>

                <!-- Linha 11: Campos vazios para preencher -->
                <tr class="row11">
                    <td class="col1"><input type="text" value="" data-field="marca"></td>
                    <td class="col2"><input type="text" value="" data-field="modelo"></td>
                    <td class="col3"><input type="text" value="" data-field="num_serie"></td>
                    <td class="col4"><input type="text" value="" data-field="ano_fabric"></td>
                </tr>

                <!-- Linha 12: Observações -->
                <tr class="row12">
                    <td class="col1">
                        <p><strong>13 Observações:</strong></p>
                        <textarea rows="8" data-field="observacoes"><?php echo htmlspecialchars($produto['obs'] ?? ''); ?></textarea>
                        <br><br>
                        <label>
                            <input type="checkbox" data-field="check_conforme"> 
                            14 ( ) CONFORME - O bem patrimonial acima foi encontrado e confere com as informações descritas.
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" data-field="check_nao_conforme"> 
                            15 ( ) NÃO CONFORME - O bem patrimonial acima NÃO foi encontrado ou NÃO confere com as informações descritas.
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" data-field="check_baixa"> 
                            16 ( ) BAIXA - Solicitada a baixa do bem patrimonial acima do inventário.
                        </label>
                    </td>
                </tr>

                <!-- Linha 13: Responsável -->
                <tr class="row13">
                    <td class="col1">C</td>
                    <td class="col2">RESPONSÁVEL PELO PREENCHIMENTO</td>
                </tr>

                <!-- Linha 14: Rótulos Nome/Função/Data -->
                <tr class="row14">
                    <td class="col1">17 Nome</td>
                    <td class="col2">18 Função</td>
                    <td class="col3">19 Data</td>
                </tr>

                <!-- Linhas 15-19: 5 responsáveis -->
                <tr class="row15">
                    <td class="col1"><input type="text" value="" data-field="resp1_nome"></td>
                    <td class="col2"><input type="text" value="" data-field="resp1_funcao"></td>
                    <td class="col3"><input type="text" value="" data-field="resp1_data"></td>
                </tr>
                <tr class="row16">
                    <td class="col1"><input type="text" value="" data-field="resp2_nome"></td>
                    <td class="col2"><input type="text" value="" data-field="resp2_funcao"></td>
                    <td class="col3"><input type="text" value="" data-field="resp2_data"></td>
                </tr>
                <tr class="row17">
                    <td class="col1"><input type="text" value="" data-field="resp3_nome"></td>
                    <td class="col2"><input type="text" value="" data-field="resp3_funcao"></td>
                    <td class="col3"><input type="text" value="" data-field="resp3_data"></td>
                </tr>
                <tr class="row18">
                    <td class="col1"><input type="text" value="" data-field="resp4_nome"></td>
                    <td class="col2"><input type="text" value="" data-field="resp4_funcao"></td>
                    <td class="col3"><input type="text" value="" data-field="resp4_data"></td>
                </tr>
                <tr class="row19">
                    <td class="col1"><input type="text" value="" data-field="resp5_nome"></td>
                    <td class="col2"><input type="text" value="" data-field="resp5_funcao"></td>
                    <td class="col3"><input type="text" value="" data-field="resp5_data"></td>
                </tr>

                <!-- Linha 20: Vistoriado por -->
                <tr class="row20">
                    <td class="col1">D</td>
                    <td class="col2">VISTORIADO POR (Comissão Regional de Inventário)</td>
                </tr>

                <!-- Linha 21: Observações da Comissão -->
                <tr class="row21">
                    <td colspan="3">
                        <strong>20 Observações da Comissão:</strong>
                        <textarea rows="2" data-field="obs_comissao"></textarea>
                    </td>
                </tr>

                <!-- Linha 22: Rótulos Nome/Função/Data (Comissão) -->
                <tr class="row22">
                    <td class="col1">21 Nome</td>
                    <td class="col2">22 Função</td>
                    <td class="col3">23 Data</td>
                </tr>

                <!-- Linhas 23-24: 2 membros da comissão -->
                <tr class="row23">
                    <td class="col1"><input type="text" value="" data-field="comissao1_nome"></td>
                    <td class="col2"><input type="text" value="" data-field="comissao1_funcao"></td>
                    <td class="col3"><input type="text" value="" data-field="comissao1_data"></td>
                </tr>
                <tr class="row24">
                    <td class="col1"><input type="text" value="" data-field="comissao2_nome"></td>
                    <td class="col2"><input type="text" value="" data-field="comissao2_funcao"></td>
                    <td class="col3"><input type="text" value="" data-field="comissao2_data"></td>
                </tr>
            </table>
        </div>
    </section>

    <!-- RODAPÉ -->
    <section class="rodape">
        <table>
            <tr class="row1">
                <td class="col1"></td>
                <td class="col2">Página <?php echo $pagina_atual; ?> de <?php echo $total_paginas; ?></td>
                <td class="col3"></td>
            </tr>
        </table>
    </section>
</div>

<?php if ($pagina_atual < $total_paginas): ?>
    <div style="page-break-after: always;"></div>
<?php endif; ?>

<?php endforeach; ?>

<!-- CSS de impressão -->
<style>
@media print {
    body { margin: 0; padding: 0; }
    .a4 { page-break-after: always; }
    .a4:last-child { page-break-after: auto; }
    
    @page {
        size: A4 portrait;
        margin: 0;
    }
}
</style>

</body>
</html>
