<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Parisienne&display=swap" rel="stylesheet">
    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
        }
        div.a4{
            width: 214mm;
            height: 295mm;
            display: flex;
            flex-direction: column;
        }
        section.cabecalho{
            display: flex;
            width: 203mm;
            height: 25mm;
            margin-block: 3mm;
            margin-inline: auto;
            overflow: hidden;
            margin-top:5mm;
        }
        section.cabecalho table {
            border-collapse: collapse;
            width: 100%;
            height: 99%;
            margin: auto;
        }
        
        section.cabecalho table th, th {
            border: 1px solid #000;
            text-align: left;
        }
        
        section.cabecalho table th {
            background-color: #fff;
            font-weight: normal;
        }
        section.cabecalho table th.col1{
            text-align: center;
            width: 27mm ;
        }
        section.cabecalho table th.col2{
            text-align: center;
            width: 122mm ;
        }
        section.cabecalho table th.col3{
            text-align: right;
            width: 28mm;
            font-size:small;
        }
        section.cabecalho table th.col4{
            text-align: center;
            width: 21mm;
            font-size:small;
        }
        section.cabecalho table tr.row1 th.col1{
            font-weight: bold;
            font-size: xx-large;
        }
        section.cabecalho table tr.row1 th.col2{
           font-weight: bold;
           font-size: x-large;
        }
        section.cabecalho table tr.row4 th.col1{
            font-size:large;
        }
        section.cabecalho table tr.row4 th.col2{
           font-size:large;
        }
        section.conteudo{
            border:1px solid #000;
            margin-inline:auto;
            width: 203mm;
            height: 252mm;
            display: flex;
            flex-direction: column;
        }
        section.conteudo h1{
            font-size:medium;
            width: 95%;
            margin-inline: auto;
            margin-block: 1.5mm;
        }
        section.conteudo div.conteudo{
            margin-inline:auto;
            width: 95%;
            height: 97.5%;
            display: flex;
            margin-bottom: 2.5%;
            flex-direction: column;
            border: 2px solid #000;
        }
        section.conteudo div.conteudo table {
            border-collapse: collapse;
            width: 100%;
            height: 99%;
            margin: auto;
            margin-top: -1px;
        }
        
        section.conteudo div.conteudo table td, td {
            border: 1px solid #000;
            text-align: left;
        }
        
        section.conteudo div.conteudo table td {
            background-color: #fff;
            font-weight: bold;
            font-size: small;
        }
        section.conteudo div.conteudo table tr.row1{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row2{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row3{
            height: 5mm;
        }
        section.conteudo div.conteudo table tr.row4{
            height: 5mm;
            flex: 1;
        }
        section.conteudo div.conteudo table tr.row5{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row6{
            height: 5mm;
        }
        section.conteudo div.conteudo table tr.row7{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row8{
            height: 5mm;
        }
        section.conteudo div.conteudo table tr.row9{
            height: 15mm;
        }
        section.conteudo div.conteudo table tr.row10{
            height: 5mm;
        }
        section.conteudo div.conteudo table tr.row11{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row12{
            height: 50mm;
        }
        section.conteudo div.conteudo table tr.row13{
            height: 5mm;
        }
        section.conteudo div.conteudo table tr.row14{
            height: 5mm;
        }
        section.conteudo div.conteudo table tr.row15{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row16{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row17{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row18{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row19{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row20{
            height: 5mm;
        }
        section.conteudo div.conteudo table tr.row21{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row22{
            height: 5mm;
        }
        section.conteudo div.conteudo table tr.row23{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row24{
            height: 10mm;
        }
        section.conteudo div.conteudo table tr.row1 td.col1{
            width: 70%;
            color: #999;
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row1 td.col2{
            width: 30%;
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row2 td.col1{
            width: 70%;
            text-align: center;
        }
        section.conteudo div.conteudo table tr.row2 td.col2{
            width: 30%;
            font-size: x-small;
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row3 td.col1{
            width: 5%;
            text-align: center;
        }
        section.conteudo div.conteudo table tr.row3 td.col2{
            width: 95%;
            background-color: #999;
            padding-left: 3mm;
            color: #fff;
        }
        section.conteudo div.conteudo table tr.row4 td.col1{
            width: 33%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row4 td.col2{
            width: 33%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row4 td.col3{
            width: 33%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row5 td.col1{
            width: 33%;
        }
        section.conteudo div.conteudo table tr.row5 td.col2{
            width: 33%;
        }
        section.conteudo div.conteudo table tr.row5 td.col3{
            width: 33%;
        }
        section.conteudo div.conteudo table tr.row6 td.col1{
            width: 33%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row6 td.col2{
            width: 33%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row6 td.col3{
            width: 33%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row7 td.col1{
            width: 33%;
        }
        section.conteudo div.conteudo table tr.row7 td.col2{
            width: 33%;
        }
        section.conteudo div.conteudo table tr.row7 td.col3{
            width: 33%;
        }
        section.conteudo div.conteudo table tr.row8 td.col1{
            width: 5%;
            text-align: center;
        }
        section.conteudo div.conteudo table tr.row8 td.col2{
            width: 95%;
            background-color: #999;
            padding-left: 3mm;
            color: #fff;
        }
        section.conteudo div.conteudo table tr.row10 td.col1{
            width: 25%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row10 td.col2{
            width: 25%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row10 td.col3{
            width: 25%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row10 td.col4{
            width: 25%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }

        section.conteudo div.conteudo table tr.row12 td.col1 p{ 
            font-size: x-small;
            font-weight: normal;
            width: 95%;
            margin-inline:2.5%;
        }
        section.conteudo div.conteudo table tr.row12 td.col1 label{ 
            font-size: x-small;
            font-weight: normal;
            width: 95%;
            margin-inline:2.5%;
        }
        section.conteudo div.conteudo table tr.row13 td.col1{
            width: 5%;
            text-align: center;
        }
        section.conteudo div.conteudo table tr.row13 td.col2{
            width: 95%;
            background-color: #999;
            padding-left: 3mm;
            color: #fff;
        }
        section.conteudo div.conteudo table tr.row14 td.col1{
            width: 20%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row14 td.col2{
            width: 40%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row14 td.col3{
            width: 40%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row15 td.col1{
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row15 td.col2{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row15 td.col3{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row16 td.col1{
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row16 td.col2{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row16 td.col3{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row17 td.col1{
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row17 td.col2{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row17 td.col3{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row18 td.col1{
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row18 td.col2{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row18 td.col3{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row19 td.col1{
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row19 td.col2{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row19 td.col3{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row20 td.col1{
            width: 5%;
            text-align: center;
        }
        section.conteudo div.conteudo table tr.row20 td.col2{
            width: 95%;
            background-color: #999;
            padding-left: 3mm;
            color: #fff;
        }
        section.conteudo div.conteudo table tr.row21 td.col1{
            padding-left: 3mm;
            font-size: x-small;
            font-weight: normal;
        }
        section.conteudo div.conteudo table tr.row22 td.col1{
            width: 20%;
            background-color: #ccc;
        }
        section.conteudo div.conteudo table tr.row22 td.col2{
            width: 40%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row22 td.col3{
            width: 40%;
            background-color: #ccc;
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row23 td.col1{
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row23 td.col2{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row23 td.col3{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row24 td.col1{
            padding-left: 3mm;
            font-size: x-small;
        }
        section.conteudo div.conteudo table tr.row24 td.col2{
            padding-left: 3mm;
        }
        section.conteudo div.conteudo table tr.row24 td.col3{
            padding-left: 3mm;
        }

        section.rodape{
            margin-inline:auto;
            width: 203mm;
            height: 10mm;
            display: flex;
            flex-direction: column;
            margin-top:3mm;
        }

        section.rodape table {
            border-collapse: collapse;
            width: 100%;
            height: 99%;
            margin: auto;
        }
        
        section.rodape table td, td {
            border: 1px solid #000;
            text-align: left;
        }
        
        section.rodape table td {
            background-color: #fff;
            font-weight: bold;
            font-size: small;
        }
        section.rodape table tr.row1 td.col1 {
            width: 20%;
        }
        section.rodape table tr.row1 td.col2 {
            width: 60%;
            text-align: center;
        }
        section.rodape table tr.row1 td.col3 {
            width: 20%;
        }
        section.conteudo div.conteudo table tr td input[type="text"]{
            color: #0066CC;
            width: 100%;
            text-align: center;
            font-size: large;
            border:none;
            font-family: "Parisienne", cursive;
            font-weight: 400;
            font-style: normal;
        }
    </style>
</head>
<body>
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
                            <input type="text" name="" id="">
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
                            <input type="text" name="" id="">
                        </td>
                        <td class="col2">
                            <input type="text" name="" id="">
                        </td>
                        <td class="col3">
                            <input type="text" name="" id="">
                        </td>
                    </tr>
                    <tr class="row6">
                        <td class="col1">CNPJ da Administração</td>
                        <td class="col2">N° Relatório</td>
                        <td class="col3">Casa de Oração</td>
                    </tr>
                    <tr class="row7">
                        <td class="col1">
                            <input type="text" name="" id="">
                        </td>
                        <td class="col2">
                            <input type="text" name="" id="">
                        </td>
                        <td class="col3">
                            <input type="text" name="" id="">
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
                            <input type="text" name="" id="">
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
                            <input type="text" name="" id="">
                        </td>
                        <td class="col2">
                            <input type="text" name="" id="">
                        </td>
                        <td class="col3">
                            <input type="text" name="" id="">
                        </td>
                        <td class="col4">
                            <input type="text" name="" id="">
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
                                <input type="checkbox" name="" id="">
                                O bem tem mais de cinco anos de uso e o documento fiscal de
                                aquisição está anexo.
                            </label><br>
                            <label>
                                <input type="checkbox" name="" id="">
                                O bem tem mais de cinco anos de uso, porém o documento fiscal
                                de aquisição foi extraviado.
                            </label><br>
                            <label>
                                <input type="checkbox" name="" id="">
                                O bem tem até cinco anos de uso e o documento fiscal de
                                aquisição está anexo.
                            </label><br><br>
                            <p>
                                Por ser verdade firmamos esta declaração.
                            </p><br>
                            <label>
                                Local e data: 
                                <input type="text" name="" id="">
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
                        </td>
                        <td class="col3">
                        </td>
                    </tr>
                    <tr class="row16">
                        <td class="col1">Endereço</td>
                        <td class="col2">
                        </td>
                        <td class="col3">
                        </td>
                    </tr>
                    <tr class="row17">
                        <td class="col1">CPF</td>
                        <td class="col2">
                        </td>
                        <td class="col3">
                        </td>
                    </tr>
                    <tr class="row18">
                        <td class="col1">RG</td>
                        <td class="col2">
                        </td>
                        <td class="col3">
                        </td>
                    </tr>
                    <tr class="row19">
                        <td class="col1">Assinatura</td>
                        <td class="col2">
                        </td>
                        <td class="col3">
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
                            <input type="text" name="" id="">
                        </td>
                        <td class="col3">
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
</body>
</html>