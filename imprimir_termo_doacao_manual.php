<?php
$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Declaração de Doação de Bem Móvel</title>
    <style>
        @font-face {
            font-family: 'Caveat';
            src: url('fonts/Caveat-VariableFont_wght.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'DancingScript';
            src: url('fonts/DancingScript-VariableFont_wght.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .no-print {
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-voltar {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }

        .btn-imprimir {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        .btn-voltar:hover {
            background: #5a6268;
        }

        .btn-imprimir:hover {
            background: #0056b3;
        }

        .formulario {
            background: white;
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        /* Tabela principal externa */
        .tabela-externa {
            border: 3px solid #000;
            width: 100%;
        }

        /* Cabeçalho CCB */
        .cabecalho-ccb {
            border-collapse: collapse;
            width: 100%;
        }

        .cabecalho-ccb td {
            border: 2px solid #000;
            padding: 8px;
            font-size: 11px;
        }

        .ccb-logo {
            width: 100px;
            text-align: center;
            font-weight: bold;
            font-size: 36px;
            vertical-align: middle;
        }

        .titulo-manual {
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            vertical-align: middle;
        }

        .info-label {
            width: 100px;
            background: #d9d9d9;
            font-weight: bold;
            font-size: 10px;
            text-align: left;
            vertical-align: middle;
        }

        .info-valor {
            width: 80px;
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            vertical-align: middle;
        }

        .assunto-label {
            width: 100px;
            background: #d9d9d9;
            font-weight: bold;
            font-size: 10px;
            text-align: left;
        }

        .assunto-texto {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
        }

        /* Título do formulário */
        .titulo-formulario {
            background: #000;
            color: white;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
            text-align: left;
        }

        /* Título da declaração */
        .titulo-declaracao {
            border: 2px solid #000;
            padding: 10px;
            margin: 15px 0;
        }

        .titulo-declaracao-principal {
            font-weight: bold;
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
        }

        .titulo-declaracao-subtitulo {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .formulario-info {
            text-align: right;
            font-size: 10px;
        }

        .formulario-numero {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3px;
        }

        /* Seções */
        .secao {
            border: 2px solid #000;
            margin-bottom: 15px;
        }

        .secao-titulo {
            background: #808080;
            color: white;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 11px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .letra-secao {
            background: #404040;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        .secao-conteudo {
            padding: 10px;
        }

        /* Tabela interna das seções */
        .tabela-secao {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-secao td {
            border: 2px solid #000;
            padding: 5px 8px;
            font-size: 10px;
            vertical-align: top;
        }

        .tabela-secao td.campo-label {
            font-weight: bold;
            background: #f0f0f0;
            width: 30%;
        }

        .tabela-secao td.campo-valor {
            background: white;
        }

        .tabela-secao input[type="text"] {
            width: 100%;
            border: none;
            font-size: 10px;
            font-family: Arial, sans-serif;
            padding: 2px;
            background: transparent;
        }

        .tabela-secao textarea {
            width: 100%;
            border: none;
            font-size: 10px;
            font-family: Arial, sans-serif;
            padding: 2px;
            background: transparent;
            resize: none;
            min-height: 120px;
        }

        /* Seção B - Descrição */
        .texto-declaracao {
            font-size: 10px;
            line-height: 1.5;
            text-align: justify;
            margin: 10px 0;
        }

        .checkbox-options {
            margin: 10px 0;
        }

        .checkbox-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 8px 0;
            font-size: 10px;
        }

        .checkbox-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .local-data {
            margin: 20px 0 10px 0;
            font-size: 10px;
        }

        /* Seção C - Doador */
        .tabela-doador {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tabela-doador td {
            border: 2px solid #000;
            padding: 5px 8px;
            font-size: 10px;
        }

        .tabela-doador .header-doador {
            text-align: center;
            font-weight: bold;
            background: #f0f0f0;
        }

        .tabela-doador .label-row {
            font-weight: bold;
            width: 120px;
            background: #f0f0f0;
        }

        .espaco-assinatura {
            height: 60px;
            border: 2px solid #000;
        }

        /* Seção D - Termo de Aceite */
        .tabela-aceite {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .tabela-aceite td {
            border: 2px solid #000;
            padding: 5px 8px;
            font-size: 10px;
        }

        .tabela-aceite .label-col {
            font-weight: bold;
            background: #f0f0f0;
            width: 150px;
        }

        /* Rodapé */
        .rodape-externa {
            border: 3px solid #000;
            border-top: none;
            width: 100%;
        }

        .rodape {
            text-align: center;
            padding: 10px;
            font-size: 11px;
            border-top: 2px solid #000;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none;
            }

            .formulario {
                box-shadow: none;
                max-width: 100%;
                padding: 0;
            }

            /* Aplicar fonte manuscrita e cor azul de caneta nos inputs ao imprimir */
            input[type="text"],
            textarea {
                font-family: 'Caveat', cursive !important;
                color: #1a4d8f !important;
                font-size: 14px !important;
                font-weight: 500;
            }

            textarea {
                font-size: 13px !important;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <a href="imprecoes.php?id=<?php echo $id_planilha; ?>" class="btn-voltar">← Voltar</a>
        <button onclick="window.print()" class="btn-imprimir">🖨️ Imprimir (Ctrl+P)</button>
        
        <div style="margin-top: 15px;">
            <label for="fonte-select" style="font-size: 14px; margin-right: 10px;">✍️ Estilo da letra manuscrita:</label>
            <select id="fonte-select" style="padding: 8px; font-size: 14px; border-radius: 4px; border: 1px solid #ccc;">
                <option value="Caveat">Caveat (Casual)</option>
                <option value="DancingScript">Dancing Script (Elegante)</option>
            </select>
        </div>
    </div>

    <div class="formulario">
        <div class="tabela-externa">
            <!-- Cabeçalho CCB -->
            <table class="cabecalho-ccb">
                <tr>
                    <td class="ccb-logo" rowspan="3">CCB</td>
                    <td class="titulo-manual" rowspan="3">MANUAL ADMINISTRATIVO</td>
                    <td class="info-label">SEÇÃO:</td>
                    <td class="info-valor">14</td>
                </tr>
                <tr>
                    <td class="info-label">FL./FLS:</td>
                    <td class="info-valor">36/46</td>
                </tr>
                <tr>
                    <td class="info-label">DATA REVISÃO:</td>
                    <td class="info-valor">24/09/2019</td>
                </tr>
                <tr>
                    <td class="assunto-label">ASSUNTO</td>
                    <td class="assunto-texto" colspan="3">PATRIMÔNIO – BENS MÓVEIS</td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="info-label">EDIÇÃO:</td>
                    <td class="info-valor">6</td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="info-label">REVISÃO:</td>
                    <td class="info-valor">1</td>
                </tr>
            </table>

            <div class="titulo-formulario">
                FORMULÁRIO 14.1: DECLARAÇÃO DE DOAÇÃO DE BEM MÓVEL
            </div>

            <!-- Título da Declaração -->
            <div class="titulo-declaracao">
                <div class="titulo-declaracao-principal">CONGREGAÇÃO CRISTÃ NO BRASIL</div>
                <div class="titulo-declaracao-subtitulo">DECLARAÇÃO DE DOAÇÃO DE BENS MÓVEIS</div>
                <div class="formulario-info">
                    <div class="formulario-numero">FORMULÁRIO 14.1</div>
                    <div>Data Emissão: _____ / _____ / _________</div>
                </div>
            </div>

            <!-- Seção A: Localidade Recebedora -->
            <div class="secao">
                <div class="secao-titulo">
                    <span class="letra-secao">A</span>
                    <span>LOCALIDADE RECEBEDORA</span>
                </div>
                <div class="secao-conteudo">
                    <table class="tabela-secao">
                        <tr>
                            <td class="campo-label">Administração</td>
                            <td class="campo-valor" colspan="3"><input type="text" id="administracao"></td>
                            <td class="campo-label">Cidade</td>
                            <td class="campo-valor" colspan="3"><input type="text" id="cidade"></td>
                            <td class="campo-label">Setor</td>
                            <td class="campo-valor"><input type="text" id="setor"></td>
                        </tr>
                        <tr>
                            <td class="campo-label">CNPJ da Administração</td>
                            <td class="campo-valor" colspan="3"><input type="text" id="cnpj"></td>
                            <td class="campo-label">N° Relatório</td>
                            <td class="campo-valor" colspan="3"><input type="text" id="relatorio"></td>
                            <td class="campo-label">Casa de Oração</td>
                            <td class="campo-valor"><input type="text" id="casa_oracao"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Seção B: Descrição do Bem -->
            <div class="secao">
                <div class="secao-titulo">
                    <span class="letra-secao">B</span>
                    <span>DESCRIÇÃO DO BEM</span>
                </div>
                <div class="secao-conteudo">
                    <table class="tabela-secao">
                        <tr>
                            <td class="campo-valor" colspan="10">
                                <textarea id="descricao_bem" placeholder="Descrição do bem..."></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td class="campo-label">N° Nota fiscal</td>
                            <td class="campo-valor" colspan="2"><input type="text" id="nota_fiscal"></td>
                            <td class="campo-label">Data de emissão</td>
                            <td class="campo-valor" colspan="2"><input type="text" id="data_emissao"></td>
                            <td class="campo-label">Valor</td>
                            <td class="campo-valor" colspan="1"><input type="text" id="valor"></td>
                            <td class="campo-label">Fornecedor</td>
                            <td class="campo-valor"><input type="text" id="fornecedor"></td>
                        </tr>
                    </table>

                    <div class="texto-declaracao">
                        Declaramos que estamos doando à CONGREGAÇÃO CRISTÃ NO BRASIL o bem acima descrito, de nossa propriedade, livre e desembaraçado de dívidas e ônus, para uso na Casa de Oração acima identificada.
                    </div>

                    <div class="checkbox-options">
                        <div class="checkbox-item">
                            <input type="checkbox" id="check1">
                            <label for="check1">O bem tem mais de cinco anos de uso e o documento fiscal de aquisição está anexo.</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="check2">
                            <label for="check2">O bem tem mais de cinco anos de uso, porém o documento fiscal de aquisição foi extraviado.</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="check3">
                            <label for="check3">O bem tem até cinco anos de uso e o documento fiscal de aquisição está anexo.</label>
                        </div>
                    </div>

                    <div class="texto-declaracao">
                        Por ser verdade firmamos esta declaração.
                    </div>

                    <div class="local-data">
                        Local e data: _________________________________________________________________
                    </div>
                </div>
            </div>

            <!-- Seção C: Doador -->
            <div class="secao">
                <div class="secao-titulo">
                    <span class="letra-secao">C</span>
                    <span>DOADOR</span>
                </div>
                <div class="secao-conteudo">
                    <table class="tabela-doador">
                        <tr>
                            <td class="header-doador" colspan="5">Dados do doador</td>
                            <td class="header-doador" colspan="5">Dados do cônjuge</td>
                        </tr>
                        <tr>
                            <td class="label-row">Nome</td>
                            <td colspan="9"><input type="text" id="nome_doador"></td>
                        </tr>
                        <tr>
                            <td class="label-row">Endereço</td>
                            <td colspan="9"><input type="text" id="endereco"></td>
                        </tr>
                        <tr>
                            <td class="label-row">CPF</td>
                            <td colspan="9"><input type="text" id="cpf"></td>
                        </tr>
                        <tr>
                            <td class="label-row">RG</td>
                            <td colspan="9"><input type="text" id="rg"></td>
                        </tr>
                        <tr>
                            <td class="label-row">Assinatura</td>
                            <td colspan="4" class="espaco-assinatura"></td>
                            <td colspan="5" class="espaco-assinatura"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Seção D: Termo de Aceite -->
            <div class="secao">
                <div class="secao-titulo">
                    <span class="letra-secao">D</span>
                    <span>TERMO DE ACEITE DA DOAÇÃO</span>
                </div>
                <div class="secao-conteudo">
                    <div class="texto-declaracao">
                        A Congregação Cristã No Brasil aceita a presente doação por atender necessidade do momento.
                    </div>

                    <table class="tabela-aceite">
                        <tr>
                            <td class="label-col">Administrador/<br>Assessor</td>
                            <td><input type="text" id="administrador_assessor"></td>
                            <td class="label-col">Assinatura</td>
                            <td class="espaco-assinatura"></td>
                        </tr>
                        <tr>
                            <td class="label-col">Doador</td>
                            <td><input type="text" id="doador_nome"></td>
                            <td class="label-col">Assinatura</td>
                            <td class="espaco-assinatura"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="rodape-externa">
            <div class="rodape">
                sp.saopaulo.manualadm@congregacao.org.br
            </div>
        </div>
    </div>
</body>
</html>