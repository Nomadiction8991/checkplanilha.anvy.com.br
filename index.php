<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Checklist - Bens Imobilizados</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .upload-section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; }
        .search-section { margin-bottom: 20px; }
        #codigoInput { padding: 10px; font-size: 16px; width: 300px; }
        .resultado { margin-top: 20px; padding: 15px; border-radius: 5px; }
        .sucesso { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .erro { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .checado { background-color: #90EE90; }
        .progress { width: 100%; background-color: #f0f0f0; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 20px; background-color: #4CAF50; border-radius: 5px; width: 0%; transition: width 0.3s; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema de Checklist - Bens Imobilizados</h1>
        
        <!-- Seção de Upload -->
        <div class="upload-section">
            <h3>Upload da Planilha</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                <!-- Alterar esta linha no index.php -->
<input type="file" name="planilha" accept=".csv,.xls,.xlsx" required>
                <button type="submit">Enviar Planilha</button>
            </form>
            <div class="progress" style="display: none;">
                <div class="progress-bar"></div>
            </div>
        </div>

        <!-- Seção de Busca -->
        <div class="search-section">
            <h3>Checklist de Produtos</h3>
            <input type="text" id="codigoInput" placeholder="Digite ou escaneie o código do produto..." autofocus>
            <div id="resultado"></div>
        </div>

        <!-- Listagem de Itens Checados -->
        <div id="listagemChecados">
            <h3>Itens Checados Recentemente</h3>
            <div id="listaItens"></div>
        </div>

        <!-- Estatísticas -->
        <div id="estatisticas">
            <h3>Estatísticas</h3>
            <div id="statsContent"></div>
        </div>
    </div>

    <script>
        // Variável para controle de atualização em tempo real
        let ultimaAtualizacao = 0;

        $(document).ready(function() {
            // Focar no campo de código
            $('#codigoInput').focus();
            
            // Atualizar lista a cada 3 segundos
            setInterval(atualizarLista, 3000);
            atualizarEstatisticas();
            
            // Upload da planilha
            $('#uploadForm').on('submit', function(e) {
                e.preventDefault();
                enviarPlanilha();
            });
            
            // Buscar produto por código
            $('#codigoInput').on('keypress', function(e) {
                if (e.which === 13) { // Enter
                    buscarProduto($(this).val());
                    $(this).val('');
                }
            });
        });

        function enviarPlanilha() {
            var formData = new FormData($('#uploadForm')[0]);
            $('.progress').show();
            
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
                    
                    var result = JSON.parse(response);
                    mostrarResultado(result.message, result.success ? 'sucesso' : 'erro');
                    
                    if (result.success) {
                        atualizarLista();
                        atualizarEstatisticas();
                    }
                }
            });
        }

        function buscarProduto(codigo) {
            if (!codigo.trim()) return;
            
            $.post('check_produto.php', { codigo: codigo }, function(response) {
                var result = JSON.parse(response);
                
                if (result.success) {
                    mostrarResultado('Produto checado com sucesso: ' + result.produto.nome, 'sucesso');
                    atualizarLista();
                    atualizarEstatisticas();
                } else {
                    mostrarResultado(result.message, 'erro');
                }
            });
        }

        function atualizarLista() {
            $.get('lista_itens.php', { ultima: ultimaAtualizacao }, function(response) {
                var data = JSON.parse(response);
                $('#listaItens').html(data.html);
                ultimaAtualizacao = data.ultimaAtualizacao;
            });
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