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
            background: white;
            max-width: 210mm;
            margin: 0 auto 20px auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .botoes-topo {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
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

        .fonte-selector {
            margin-top: 15px;
        }

        .fonte-selector label {
            font-size: 14px;
            margin-right: 10px;
            font-weight: bold;
        }

        .fonte-selector select {
            padding: 8px;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .formulario-inputs {
            margin-top: 20px;
        }

        .secao-input {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }

        .secao-input h3 {
            margin-bottom: 15px;
            color: #007bff;
            font-size: 16px;
        }

        .campo-grupo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .campo-input {
            display: flex;
            flex-direction: column;
        }

        .campo-input label {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        .campo-input input,
        .campo-input textarea {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            font-family: Arial, sans-serif;
        }

        .campo-input textarea {
            resize: vertical;
            min-height: 80px;
        }

        .campo-input.completo {
            grid-column: 1 / -1;
        }

        /* Container do formulário impresso */
        .formulario-container {
            background: white;
            max-width: 210mm;
            margin: 0 auto;
            position: relative;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .formulario-imagem {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Camada de overlay para os textos */
        .texto-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .texto-campo {
            position: absolute;
            font-family: 'Caveat', cursive;
            color: #1a4d8f;
            font-size: 16px;
            font-weight: 600;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Posicionamento dos campos - ajustar conforme necessário */
        .campo-administracao { top: 17.5%; left: 13%; width: 20%; }
        .campo-cidade { top: 17.5%; left: 40%; width: 20%; }
        .campo-setor { top: 17.5%; left: 70%; width: 15%; }
        .campo-cnpj { top: 21.5%; left: 19%; width: 18%; }
        .campo-relatorio { top: 21.5%; left: 45%; width: 15%; }
        .campo-casa-oracao { top: 21.5%; left: 71%; width: 14%; }
        
        .campo-descricao { top: 28%; left: 8%; width: 84%; height: 15%; font-size: 14px; }
        .campo-nota-fiscal { top: 44%; left: 13%; width: 12%; }
        .campo-data-emissao { top: 44%; left: 32%; width: 12%; }
        .campo-valor { top: 44%; left: 50%; width: 10%; }
        .campo-fornecedor { top: 44%; left: 67%; width: 18%; }
        
        .campo-local-data { top: 59%; left: 15%; width: 50%; }
        
        .campo-nome-doador { top: 66%; left: 10%; width: 80%; }
        .campo-endereco { top: 69.5%; left: 12%; width: 78%; }
        .campo-cpf { top: 73%; left: 8%; width: 35%; }
        .campo-rg { top: 73%; left: 48%; width: 40%; }
        
        .campo-administrador { top: 87%; left: 18%; width: 30%; }
        .campo-doador-aceite { top: 90.5%; left: 12%; width: 30%; }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .formulario-container {
                box-shadow: none;
                max-width: 100%;
                page-break-inside: avoid;
            }

            body[data-fonte="Caveat"] .texto-campo {
                font-family: 'Caveat', cursive !important;
            }

            body[data-fonte="DancingScript"] .texto-campo {
                font-family: 'DancingScript', cursive !important;
                font-size: 15px;
            }

            @page {
                size: A4;
                margin: 10mm;
            }
        }
    </style>
</head>
<body data-fonte="Caveat">
    
    <!-- Formulário de Inputs (não imprime) -->
    <div class="no-print">
        <div class="botoes-topo">
            <a href="imprecoes.php?id=<?php echo $id_planilha; ?>" class="btn-voltar">← Voltar</a>
            <button onclick="window.print()" class="btn-imprimir">🖨️ Imprimir (Ctrl+P)</button>
            
            <div class="fonte-selector">
                <label for="fonte-select">✍️ Estilo da letra:</label>
                <select id="fonte-select">
                    <option value="Caveat">Caveat (Casual)</option>
                    <option value="DancingScript">Dancing Script (Elegante)</option>
                </select>
            </div>
        </div>

        <div class="formulario-inputs">
            <!-- Seção A -->
            <div class="secao-input">
                <h3>📍 Seção A - Localidade Recebedora</h3>
                <div class="campo-grupo">
                    <div class="campo-input">
                        <label>Administração</label>
                        <input type="text" id="administracao" oninput="atualizarCampo('administracao')">
                    </div>
                    <div class="campo-input">
                        <label>Cidade</label>
                        <input type="text" id="cidade" oninput="atualizarCampo('cidade')">
                    </div>
                    <div class="campo-input">
                        <label>Setor</label>
                        <input type="text" id="setor" oninput="atualizarCampo('setor')">
                    </div>
                </div>
                <div class="campo-grupo">
                    <div class="campo-input">
                        <label>CNPJ da Administração</label>
                        <input type="text" id="cnpj" oninput="atualizarCampo('cnpj')">
                    </div>
                    <div class="campo-input">
                        <label>N° Relatório</label>
                        <input type="text" id="relatorio" oninput="atualizarCampo('relatorio')">
                    </div>
                    <div class="campo-input">
                        <label>Casa de Oração</label>
                        <input type="text" id="casa-oracao" oninput="atualizarCampo('casa-oracao')">
                    </div>
                </div>
            </div>

            <!-- Seção B -->
            <div class="secao-input">
                <h3>📦 Seção B - Descrição do Bem</h3>
                <div class="campo-grupo">
                    <div class="campo-input completo">
                        <label>Descrição do Bem</label>
                        <textarea id="descricao" oninput="atualizarCampo('descricao')"></textarea>
                    </div>
                </div>
                <div class="campo-grupo">
                    <div class="campo-input">
                        <label>N° Nota Fiscal</label>
                        <input type="text" id="nota-fiscal" oninput="atualizarCampo('nota-fiscal')">
                    </div>
                    <div class="campo-input">
                        <label>Data de Emissão</label>
                        <input type="text" id="data-emissao" oninput="atualizarCampo('data-emissao')">
                    </div>
                    <div class="campo-input">
                        <label>Valor</label>
                        <input type="text" id="valor" oninput="atualizarCampo('valor')">
                    </div>
                </div>
                <div class="campo-grupo">
                    <div class="campo-input completo">
                        <label>Fornecedor</label>
                        <input type="text" id="fornecedor" oninput="atualizarCampo('fornecedor')">
                    </div>
                </div>
                <div class="campo-grupo">
                    <div class="campo-input completo">
                        <label>Local e Data</label>
                        <input type="text" id="local-data" oninput="atualizarCampo('local-data')">
                    </div>
                </div>
            </div>

            <!-- Seção C -->
            <div class="secao-input">
                <h3>👤 Seção C - Doador</h3>
                <div class="campo-grupo">
                    <div class="campo-input completo">
                        <label>Nome do Doador</label>
                        <input type="text" id="nome-doador" oninput="atualizarCampo('nome-doador')">
                    </div>
                </div>
                <div class="campo-grupo">
                    <div class="campo-input completo">
                        <label>Endereço</label>
                        <input type="text" id="endereco" oninput="atualizarCampo('endereco')">
                    </div>
                </div>
                <div class="campo-grupo">
                    <div class="campo-input">
                        <label>CPF</label>
                        <input type="text" id="cpf" oninput="atualizarCampo('cpf')">
                    </div>
                    <div class="campo-input">
                        <label>RG</label>
                        <input type="text" id="rg" oninput="atualizarCampo('rg')">
                    </div>
                </div>
            </div>

            <!-- Seção D -->
            <div class="secao-input">
                <h3>✅ Seção D - Termo de Aceite</h3>
                <div class="campo-grupo">
                    <div class="campo-input">
                        <label>Administrador/Assessor</label>
                        <input type="text" id="administrador" oninput="atualizarCampo('administrador')">
                    </div>
                    <div class="campo-input">
                        <label>Doador (Nome)</label>
                        <input type="text" id="doador-aceite" oninput="atualizarCampo('doador-aceite')">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulário Impresso com Imagem de Fundo -->
    <div class="formulario-container">
        <!-- IMPORTANTE: Substitua o src pela imagem real do formulário -->
        <img src="formulario_14_1.jpg" alt="Formulário 14.1" class="formulario-imagem">
        
        <!-- Overlay com os textos preenchidos -->
        <div class="texto-overlay">
            <div class="texto-campo campo-administracao" id="texto-administracao"></div>
            <div class="texto-campo campo-cidade" id="texto-cidade"></div>
            <div class="texto-campo campo-setor" id="texto-setor"></div>
            <div class="texto-campo campo-cnpj" id="texto-cnpj"></div>
            <div class="texto-campo campo-relatorio" id="texto-relatorio"></div>
            <div class="texto-campo campo-casa-oracao" id="texto-casa-oracao"></div>
            
            <div class="texto-campo campo-descricao" id="texto-descricao"></div>
            <div class="texto-campo campo-nota-fiscal" id="texto-nota-fiscal"></div>
            <div class="texto-campo campo-data-emissao" id="texto-data-emissao"></div>
            <div class="texto-campo campo-valor" id="texto-valor"></div>
            <div class="texto-campo campo-fornecedor" id="texto-fornecedor"></div>
            
            <div class="texto-campo campo-local-data" id="texto-local-data"></div>
            
            <div class="texto-campo campo-nome-doador" id="texto-nome-doador"></div>
            <div class="texto-campo campo-endereco" id="texto-endereco"></div>
            <div class="texto-campo campo-cpf" id="texto-cpf"></div>
            <div class="texto-campo campo-rg" id="texto-rg"></div>
            
            <div class="texto-campo campo-administrador" id="texto-administrador"></div>
            <div class="texto-campo campo-doador-aceite" id="texto-doador-aceite"></div>
        </div>
    </div>

    <script>
        // Atualizar fonte selecionada
        const fonteSelect = document.getElementById('fonte-select');
        fonteSelect.addEventListener('change', function() {
            document.body.setAttribute('data-fonte', this.value);
        });

        // Função para atualizar os campos no overlay
        function atualizarCampo(campo) {
            const input = document.getElementById(campo);
            const texto = document.getElementById('texto-' + campo);
            if (input && texto) {
                texto.textContent = input.value;
            }
        }
    </script>
</body>
</html>