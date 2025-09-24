<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Checklist - Bens Imobilizados</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .upload-section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
        .search-section { margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
        .search-box { display: flex; gap: 10px; align-items: center; }
        #codigoInput { padding: 12px; font-size: 16px; flex: 1; border: 1px solid #ccc; border-radius: 4px; }
        .btn { padding: 12px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #0056b3; }
        .btn:disabled { background: #6c757d; cursor: not-allowed; }
        .resultado { margin-top: 20px; padding: 15px; border-radius: 5px; }
        .sucesso { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #343a40; color: white; }
        .checado { background-color: #d4edda; }
        .progress { width: 100%; background-color: #f0f0f0; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 20px; background-color: #28a745; border-radius: 5px; width: 0%; transition: width 0.3s; }
        .pagination { margin: 20px 0; display: flex; justify-content: center; gap: 5px; }
        .page-btn { padding: 8px 12px; border: 1px solid #ddd; background: white; cursor: pointer; }
        .page-btn.active { background: #007bff; color: white; border-color: #007bff; }
        .section-title { color: #343a40; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align: center; color: #343a40;">Sistema de Checklist - Bens Imobilizados</h1>
        
        <!-- Seção de Upload -->
        <div class="upload-section">
            <h3 class="section-title">Upload da Planilha</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="file" name="planilha" accept=".csv,.xls,.xlsx" required>
                <button type="submit" class="btn">Enviar Planilha</button>
            </form>
            <div class="progress" style="display: none;">
                <div class="progress-bar"></div>
            </div>
        </div>

        <!-- Seção de Busca -->
        <div class="search-section">
            <h3 class="section-title">Checklist de Produtos</h3>
            <div class="search-box">
                <input type="text" id="codigoInput" placeholder="Digite ou escaneie o código do produto..." autofocus>
                <button type="button" class="btn" onclick="buscarProduto()" id="buscarBtn">Marcar Check</button>
            </div>
            <div id="resultado"></div>
        </div>

        <!-- Listagem de Itens -->
        <div id="listagemItens">
            <h3 class="section-title">Lista de Itens (50 por página)</h3>
            <div id="listaItens"></div>
            <div class="pagination" id="pagination"></div>
        </div>

        <!-- Estatísticas -->
        <div id="estatisticas">
            <h3 class="section-title">Estatísticas</h3>
            <div id="statsContent"></div>
        </div>
    </div>

    <script>
        let paginaAtual = 1;
        let totalPaginas = 1;

        $(document).ready(function() {
            $('#codigoInput').focus();
            
            // Atualizar lista e estatísticas periodicamente
            setInterval(atualizarLista, 5000);
            atualizarEstatisticas();
            carregarPagina(1);
            
            // Upload da planilha
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                enviarPlanilha();
            });
            
            // Buscar com Enter
            $('#codigoInput').on('keypress', function(e) {
                if (e.which === 13) {
                    buscarProduto();
                }
            });
        });

        function enviarPlanilha() {
            var formData = new FormData($('#uploadForm')[0]);
            $('.progress').show();
            $('#uploadForm button').prop('disabled', true);
            
            $.ajax({
                url: 'upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = (evt.loaded / evt.total) * 100;
                            $('.progress-bar').width(percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    $('.progress').hide();
                    $('.progress-bar').width('0%');
                    $('#uploadForm button').prop('disabled', false);
                    
                    try {
                        var result = JSON.parse(response);
                        mostrarResultado(result.message, result.success ? 'sucesso' : 'erro');
                        
                        if (result.success) {
                            carregarPagina(1);
                            atualizarEstatisticas();
                        }
                    } catch (e) {
                        mostrarResultado('Erro ao processar resposta do servidor', 'erro');
                    }
                },
                error: function() {
                    $('.progress').hide();
                    $('#uploadForm button').prop('disabled', false);
                    mostrarResultado('Erro na comunicação com o servidor', 'erro');
                }
            });
        }

        function buscarProduto() {
            var codigo = $('#codigoInput').val().trim();
            if (!codigo) {
                mostrarResultado('Digite um código para buscar', 'erro');
                return;
            }
            
            $('#buscarBtn').prop('disabled', true).text('Processando...');
            
            $.post('check_produto.php', { codigo: codigo }, function(response) {
                $('#buscarBtn').prop('disabled', false).text('Marcar Check');
                
                try {
                    var result = JSON.parse(response);
                    
                    if (result.success) {
                        mostrarResultado('✓ Produto checado com sucesso: ' + result.produto.nome, 'sucesso');
                        $('#codigoInput').val('').focus();
                        carregarPagina(paginaAtual);
                        atualizarEstatisticas();
                    } else {
                        mostrarResultado(result.message, 'erro');
                    }
                } catch (e) {
                    mostrarResultado('Erro ao processar resposta', 'erro');
                }
            }).fail(function() {
                $('#buscarBtn').prop('disabled', false).text('Marcar Check');
                mostrarResultado('Erro na comunicação com o servidor', 'erro');
            });
        }

        function carregarPagina(pagina) {
            paginaAtual = pagina;
            $.get('lista_itens.php', { pagina: pagina }, function(response) {
                try {
                    var data = JSON.parse(response);
                    $('#listaItens').html(data.html);
                    totalPaginas = data.totalPaginas;
                    atualizarPaginacao();
                } catch (e) {
                    $('#listaItens').html('<div class="erro">Erro ao carregar lista</div>');
                }
            });
        }

        function atualizarPaginacao() {
            var paginacao = $('#pagination');
            paginacao.empty();
            
            for (let i = 1; i <= totalPaginas; i++) {
                var btn = $('<button>')
                    .addClass('page-btn')
                    .text(i)
                    .toggleClass('active', i === paginaAtual)
                    .click(function() { carregarPagina(i); });
                paginacao.append(btn);
            }
        }

        function atualizarEstatisticas() {
            $.get('estatisticas.php', function(response) {
                $('#statsContent').html(response);
            });
        }

        function mostrarResultado(mensagem, tipo) {
            $('#resultado').html('<div class="resultado ' + tipo + '">' + mensagem + '</div>');
            setTimeout(function() {
                $('#resultado').empty();
            }, 5000);
        }
    </script>
</body>
</html>