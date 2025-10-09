<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário 14.1 - Declaração de Doação de Bem Móvel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f5f5;
            padding: 20px;
        }

        .formulario-container {
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            padding: 0;
        }

        /* Tabela Externa Principal */
        .tabela-principal {
            border: 3px solid #000;
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-principal td {
            border: 2px solid #000;
            padding: 8px;
            vertical-align: middle;
        }

        /* Cabeçalho CCB */
        .ccb-logo {
            width: 100px;
            text-align: center;
            font-size: 42px;
            font-weight: bold;
            background: #fff;
        }

        .titulo-manual {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            padding: 15px;
        }

        .info-celula {
            font-size: 10px;
            background: #d9d9d9;
            font-weight: bold;
            padding: 6px 8px;
            width: 110px;
        }

        .valor-celula {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            width: 70px;
            padding: 6px;
        }

        .assunto-label {
            font-size: 10px;
            background: #d9d9d9;
            font-weight: bold;
            padding: 6px 8px;
            width: 100px;
        }

        .assunto-valor {
            text-align: center;
            font-size: 15px;
            font-weight: bold;
            padding: 8px;
        }

        /* Título do Formulário */
        .titulo-formulario {
            background: #000;
            color: white;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: bold;
        }

        /* Caixa de Título da Declaração */
        .box-titulo-declaracao {
            border: 2px solid #000;
            padding: 12px;
            margin: 15px 0;
        }

        .declaracao-titulo1 {
            font-size: 13px;
            font-weight: bold;
            color: #666;
            margin-bottom: 5px;
        }

        .declaracao-titulo2 {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .declaracao-info {
            text-align: right;
            font-size: 10px;
        }

        .declaracao-info-numero {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3px;
        }

        /* Seções */
        .secao {
            border: 2px solid #000;
            margin-bottom: 15px;
        }

        .secao-header {
            background: #808080;
            color: white;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .letra-secao {
            background: #404040;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border-radius: 2px;
        }

        .secao-conteudo {
            padding: 0;
        }

        /* Tabelas das Seções */
        .tabela-secao {
            width: 100%;
            border-collapse: collapse;
        }

        .tabela-secao td {
            border: 2px solid #000;
            padding: 6px 8px;
            font-size: 10px;
            vertical-align: middle;
        }

        .tabela-secao .label-col {
            background: #f0f0f0;
            font-weight: bold;
            width: 140px;
        }

        .tabela-secao .espaco-campo {
            background: #fff;
            height: 25px;
        }

        .tabela-secao .espaco-grande {
            height: 140px;
            vertical-align: top;
        }

        /* Seção B - Textos */
        .texto-declaracao {
            padding: 12px;
            font-size: 10px;
            line-height: 1.6;
            text-align: justify;
        }

        .checkbox-area {
            padding: 0 12px 12px 12px;
        }

        .checkbox-item {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 8px 0;
            font-size: 10px;
        }

        .checkbox-box {
            width: 18px;
            height: 18px;
            border: 2px solid #000;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .local-data-linha {
            padding: 12px;
            font-size: 10px;
            margin-top: 15px;
        }

        /* Seção C - Doador */
        .header-doador {
            text-align: center;
            font-weight: bold;
            background: #f0f0f0;
            padding: 8px;
            font-size: 10px;
        }

        .espaco-assinatura {
            height: 70px;
            background: #fff;
        }

        /* Seção D */
        .tabela-aceite {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .tabela-aceite td {
            border: 2px solid #000;
            padding: 6px 8px;
            font-size: 10px;
        }

        .tabela-aceite .label-col {
            background: #f0f0f0;
            font-weight: bold;
            width: 150px;
            vertical-align: middle;
        }

        /* Rodapé */
        .rodape-container {
            border: 3px solid #000;
            border-top: none;
            width: 100%;
        }

        .rodape {
            text-align: center;
            padding: 12px;
            font-size: 11px;
            border-top: 2px solid #000;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .formulario-container {
                box-shadow: none;
                max-width: 100%;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="formulario-container">
        <!-- Tabela Principal Externa -->
        <table class="tabela-principal">
            <tr>
                <!-- Cabeçalho CCB -->
                <td class="ccb-logo" rowspan="3">CCB</td>
                <td class="titulo-manual" rowspan="3">MANUAL ADMINISTRATIVO</td>
                <td class="info-celula">SEÇÃO:</td>
                <td class="valor-celula">14</td>
            </tr>
            <tr>
                <td class="info-celula">FL./FLS:</td>
                <td class="valor-celula">36/46</td>
            </tr>
            <tr>
                <td class="info-celula">DATA REVISÃO:</td>
                <td class="valor-celula">24/09/2019</td>
            </tr>
            <tr>
                <td class="assunto-label">ASSUNTO</td>
                <td class="assunto-valor" colspan="3">PATRIMÔNIO – BENS MÓVEIS</td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="info-celula">EDIÇÃO:</td>
                <td class="valor-celula">6</td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="info-celula">REVISÃO:</td>
                <td class="valor-celula">1</td>
            </tr>
        </table>

        <!-- Título do Formulário -->
        <div class="titulo-formulario">
            FORMULÁRIO 14.1: DECLARAÇÃO DE DOAÇÃO DE BEM MÓVEL
        </div>

        <!-- Box Título da Declaração -->
        <div class="box-titulo-declaracao">
            <div class="declaracao-titulo1">CONGREGAÇÃO CRISTÃ NO BRASIL</div>
            <div class="declaracao-titulo2">DECLARAÇÃO DE DOAÇÃO DE BENS MÓVEIS</div>
            <div class="declaracao-info">
                <div class="declaracao-info-numero">FORMULÁRIO 14.1</div>
                <div>Data Emissão</div>
            </div>
        </div>

        <!-- Seção A: Localidade Recebedora -->
        <div class="secao">
            <div class="secao-header">
                <span class="letra-secao">A</span>
                <span>LOCALIDADE RECEBEDORA</span>
            </div>
            <div class="secao-conteudo">
                <table class="tabela-secao">
                    <tr>
                        <td class="label-col">Administração</td>
                        <td class="espaco-campo" colspan="2"></td>
                        <td class="label-col">Cidade</td>
                        <td class="espaco-campo" colspan="2"></td>
                        <td class="label-col">Setor</td>
                        <td class="espaco-campo"></td>
                    </tr>
                    <tr>
                        <td class="label-col">CNPJ da Administração</td>
                        <td class="espaco-campo" colspan="2"></td>
                        <td class="label-col">N° Relatório</td>
                        <td class="espaco-campo" colspan="2"></td>
                        <td class="label-col">Casa de Oração</td>
                        <td class="espaco-campo"></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Seção B: Descrição do Bem -->
        <div class="secao">
            <div class="secao-header">
                <span class="letra-secao">B</span>
                <span>DESCRIÇÃO DO BEM</span>
            </div>
            <div class="secao-conteudo">
                <table class="tabela-secao">
                    <tr>
                        <td class="espaco-campo espaco-grande" colspan="8"></td>
                    </tr>
                    <tr>
                        <td class="label-col">N° Nota fiscal</td>
                        <td class="espaco-campo"></td>
                        <td class="label-col">Data de emissão</td>
                        <td class="espaco-campo"></td>
                        <td class="label-col">Valor</td>
                        <td class="espaco-campo"></td>
                        <td class="label-col">Fornecedor</td>
                        <td class="espaco-campo" style="width: 150px;"></td>
                    </tr>
                </table>

                <div class="texto-declaracao">
                    Declaramos que estamos doando à CONGREGAÇÃO CRISTÃ NO BRASIL o bem acima descrito, de nossa propriedade, livre e desembaraçado de dívidas e ônus, para uso na Casa de Oração acima identificada.
                </div>

                <div class="checkbox-area">
                    <div class="checkbox-item">
                        <div class="checkbox-box"></div>
                        <div>O bem tem mais de cinco anos de uso e o documento fiscal de aquisição está anexo.</div>
                    </div>
                    <div class="checkbox-item">
                        <div class="checkbox-box"></div>
                        <div>O bem tem mais de cinco anos de uso, porém o documento fiscal de aquisição foi extraviado.</div>
                    </div>
                    <div class="checkbox-item">
                        <div class="checkbox-box"></div>
                        <div>O bem tem até cinco anos de uso e o documento fiscal de aquisição está anexo.</div>
                    </div>
                </div>

                <div class="texto-declaracao">
                    Por ser verdade firmamos esta declaração.
                </div>

                <div class="local-data-linha">
                    Local e data: _______________________________________________________________
                </div>
            </div>
        </div>

        <!-- Seção C: Doador -->
        <div class="secao">
            <div class="secao-header">
                <span class="letra-secao">C</span>
                <span>DOADOR</span>
            </div>
            <div class="secao-conteudo">
                <table class="tabela-secao">
                    <tr>
                        <td class="header-doador" colspan="4">Dados do doador</td>
                        <td class="header-doador" colspan="4">Dados do cônjuge</td>
                    </tr>
                    <tr>
                        <td class="label-col">Nome</td>
                        <td class="espaco-campo" colspan="7"></td>
                    </tr>
                    <tr>
                        <td class="label-col">Endereço</td>
                        <td class="espaco-campo" colspan="7"></td>
                    </tr>
                    <tr>
                        <td class="label-col">CPF</td>
                        <td class="espaco-campo" colspan="7"></td>
                    </tr>
                    <tr>
                        <td class="label-col">RG</td>
                        <td class="espaco-campo" colspan="7"></td>
                    </tr>
                    <tr>
                        <td class="label-col">Assinatura</td>
                        <td class="espaco-assinatura" colspan="3"></td>
                        <td class="espaco-assinatura" colspan="4"></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Seção D: Termo de Aceite -->
        <div class="secao">
            <div class="secao-header">
                <span class="letra-secao">D</span>
                <span>TERMO DE ACEITE DA DOAÇÃO</span>
            </div>
            <div class="secao-conteudo">
                <div class="texto-declaracao">
                    A Congregação Cristã No Brasil aceita a presente doação por atender necessidade do momento.
                </div>
                <div style="padding: 0 12px 12px 12px;">
                    <table class="tabela-aceite">
                        <tr>
                            <td class="label-col">Administrador/<br>Assessor</td>
                            <td class="espaco-campo" style="width: 250px;"></td>
                            <td class="label-col">Assinatura</td>
                            <td class="espaco-assinatura" style="width: 200px;"></td>
                        </tr>
                        <tr>
                            <td class="label-col">Doador</td>
                            <td class="espaco-campo"></td>
                            <td class="label-col">Assinatura</td>
                            <td class="espaco-assinatura"></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Rodapé -->
        <div class="rodape-container">
            <div class="rodape">
                sp.saopaulo.manualadm@congregacao.org.br
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>