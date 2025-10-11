<?php
// Incluir o arquivo PHP que contém a lógica
require_once '../CRUD/READ/relatorio-14-1.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório 14.1 - Doação de Bem Móvel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../STYLE/relatorio-14-1.css">
</head>
<body>
    <!-- Inputs para valores que se repetem em todos os documentos -->
    <div style="margin-bottom: 20px; padding: 10px; border: 1px solid #ccc; background: #f9f9f9;">
        <h3>Valores Comuns para Todos os Documentos:</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
                <label>Administração: <input type="text" id="admin_geral" onchange="atualizarTodos('admin')"></label>
            </div>
            <div>
                <label>Cidade: <input type="text" id="cidade_geral" onchange="atualizarTodos('cidade')"></label>
            </div>
            <div>
                <label>Setor: <input type="text" id="setor_geral" onchange="atualizarTodos('setor')"></label>
            </div>
            <div>
                <label>CNPJ: <input type="text" id="cnpj_geral" onchange="atualizarTodos('cnpj')"></label>
            </div>
            <div>
                <label>N° Relatório: <input type="text" id="relatorio_geral" onchange="atualizarTodos('relatorio')"></label>
            </div>
            <div>
                <label>Casa de Oração: <input type="text" id="casa_oracao_geral" onchange="atualizarTodos('casa_oracao')"></label>
            </div>
            <div>
                <label>Administrador/Acessor: <input type="text" id="admin_acessor_geral" onchange="atualizarTodos('admin_acessor')"></label>
            </div>
        </div>
    </div>

    <?php if (count($produtos) > 0): ?>
        <?php $count = 0; ?>
        <?php foreach($produtos as $row): ?>
            <?php $count++; ?>
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
                                    <input type="text" name="cnpj" id="cnpj_<?php echo $row['id']; ?>">
                                </td>
                                <td class="col2">
                                    <input type="text" name="numero_relatorio" id="numero_relatorio_<?php echo $row['id']; ?>">
                                </td>
                                <td class="col3">
                                    <input type="text" name="casa_oracao" id="casa_oracao_<?php echo $row['id']; ?>">
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
                                    <input type="text" name="descricao_bem" id="descricao_bem_<?php echo $row['id']; ?>" value="<?php 
                                        echo htmlspecialchars($row['tipo_codigo'] . ' - ' . $row['tipo_descricao']);
                                        echo ' [' . htmlspecialchars($row['tipo_ben']) . ']';
                                        echo ' ' . htmlspecialchars($row['complemento']);
                                        if (!empty($row['dependencia_descricao'])) {
                                            echo ' (' . htmlspecialchars($row['dependencia_descricao']) . ')';
                                        }
                                    ?>" readonly style="background-color: #f0f0f0;">
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
                                    <p>
                                        Declaramos que estamos doando à CONGREGAÇÃO CRISTÃ NO BRASIL
                                        o bem acima descrito, de nossa propriedade, livre e sesembaraçado
                                        de dívidas e ônus, para uso na Casa de Oração acima identificada.
                                    </p><br>
                                    <label>
                                        <input type="checkbox" name="opcao_1" id="opcao_1_<?php echo $row['id']; ?>">
                                        O bem tem mais de cinco anos de uso e o documento fiscal de
                                        aquisição está anexo.
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="opcao_2" id="opcao_2_<?php echo $row['id']; ?>">
                                        O bem tem mais de cinco anos de uso, porém o documento fiscal
                                        de aquisição foi extraviado.
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="opcao_3" id="opcao_3_<?php echo $row['id']; ?>">
                                        O bem tem até cinco anos de uso e o documento fiscal de
                                        aquisição está anexo.
                                    </label><br><br>
                                    <p>
                                        Por ser verdade firmamos esta declaração.
                                    </p><br>
                                    <label>
                                        Local e data: 
                                        <input type="text" name="local_data" id="local_data_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($comum_planilha); ?> ____/____/_______">
                                    </label>
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
                                <td class="col2">
                                    <input type="text" name="nome_doador" id="nome_doador_<?php echo $row['id']; ?>">
                                </td>
                                <td class="col3">
                                    <input type="text" name="nome_conjuge" id="nome_conjuge_<?php echo $row['id']; ?>">
                                </td>
                            </tr>
                            <tr class="row16">
                                <td class="col1">Endereço</td>
                                <td class="col2">
                                    <input type="text" name="endereco_doador" id="endereco_doador_<?php echo $row['id']; ?>">
                                </td>
                                <td class="col3">
                                    <input type="text" name="endereco_conjuge" id="endereco_conjuge_<?php echo $row['id']; ?>">
                                </td>
                            </tr>
                            <tr class="row17">
                                <td class="col1">CPF</td>
                                <td class="col2">
                                    <input type="text" name="cpf_doador" id="cpf_doador_<?php echo $row['id']; ?>">
                                </td>
                                <td class="col3">
                                    <input type="text" name="cpf_conjuge" id="cpf_conjuge_<?php echo $row['id']; ?>">
                                </td>
                            </tr>
                            <tr class="row18">
                                <td class="col1">RG</td>
                                <td class="col2">
                                    <input type="text" name="rg_doador" id="rg_doador_<?php echo $row['id']; ?>">
                                </td>
                                <td class="col3">
                                    <input type="text" name="rg_conjuge" id="rg_conjuge_<?php echo $row['id']; ?>">
                                </td>
                            </tr>
                            <tr class="row19">
                                <td class="col1">Assinatura</td>
                                <td class="col2">
                                    <input type="text" name="assinatura_doador" id="assinatura_doador_<?php echo $row['id']; ?>">
                                </td>
                                <td class="col3">
                                    <input type="text" name="assinatura_conjuge" id="assinatura_conjuge_<?php echo $row['id']; ?>">
                                </td>
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
                                <td class="col1" colspan="3">
                                    <p>
                                        A Congregação Cristã No Brasil aceita a presente doação por
                                        atender necessidade do momento.
                                    </p>
                                </td>
                            </tr>
                            <tr class="row22">
                                <td class="col1"></td>
                                <td class="col2">Nome</td>
                                <td class="col3">Assinatura</td>
                            </tr>
                            <tr class="row23">
                                <td class="col1">Administrador/Acessor</td>
                                <td class="col2">
                                    <input type="text" name="admin_acessor" id="admin_acessor_<?php echo $row['id']; ?>">
                                </td>
                                <td class="col3">
                                    <input type="text" name="assinatura_admin" id="assinatura_admin_<?php echo $row['id']; ?>">
                                </td>
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

            <?php if ($count < count($produtos)): ?>
                <div style="page-break-after: always;"></div>
            <?php endif; ?>
            
        <?php endforeach; ?>
        
    <?php else: ?>
        <p>Nenhum produto encontrado para impressão do relatório 14.1.</p>
    <?php endif; ?>

<script>
    function atualizarTodos(tipo) {
        const valor = document.getElementById(tipo + '_geral').value;
        let selector;
        
        // Mapear os tipos gerais para os IDs específicos nos formulários
        switch(tipo) {
            case 'admin':
                selector = '[id^="administracao_"]';
                break;
            case 'cidade':
                selector = '[id^="cidade_"]';
                break;
            case 'setor':
                selector = '[id^="setor_"]';
                break;
            case 'cnpj':
                selector = '[id^="cnpj_"]';
                break;
            case 'relatorio':
                selector = '[id^="numero_relatorio_"]';
                break;
            case 'casa_oracao':
                selector = '[id^="casa_oracao_"]';
                break;
            case 'admin_acessor':
                selector = '[id^="admin_acessor_"]';
                break;
            default:
                selector = '[id^="' + tipo + '_"]';
        }
        
        const inputs = document.querySelectorAll(selector);
        
        inputs.forEach(input => {
            if (!input.id.includes('geral')) {
                input.value = valor;
            }
        });
    }
</script>
</body>
</html>