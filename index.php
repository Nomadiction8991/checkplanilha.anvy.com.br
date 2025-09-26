<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Checklist - Bens Imobilizados</title>

    <script>
// Função para abrir a câmera e escanear código
function abrirCamera() {
    // Verifica se o navegador suporta acesso à câmera
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Seu navegador não suporta acesso à câmera.');
        return;
    }
    
    // Cria um modal para a câmera
    const modal = document.createElement('div');
    modal.style.position = 'fixed';
    modal.style.top = '0';
    modal.style.left = '0';
    modal.style.width = '100%';
    modal.style.height = '100%';
    modal.style.backgroundColor = 'rgba(0,0,0,0.8)';
    modal.style.zIndex = '1000';
    modal.style.display = 'flex';
    modal.style.flexDirection = 'column';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    
    // Cria o vídeo da câmera
    const video = document.createElement('video');
    video.style.width = '80%';
    video.style.maxWidth = '500px';
    video.style.border = '2px solid white';
    video.setAttribute('autoplay', '');
    
    // Cria botão de fechar
    const btnFechar = document.createElement('button');
    btnFechar.textContent = 'Fechar Câmera';
    btnFechar.style.marginTop = '20px';
    btnFechar.style.padding = '10px 20px';
    btnFechar.style.backgroundColor = '#dc3545';
    btnFechar.style.color = 'white';
    btnFechar.style.border = 'none';
    btnFechar.style.borderRadius = '5px';
    btnFechar.style.cursor = 'pointer';
    
    // Adiciona elementos ao modal
    modal.appendChild(video);
    modal.appendChild(btnFechar);
    document.body.appendChild(modal);
    
    // Acessa a câmera traseira (environment)
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'environment'  // Usa câmera traseira
        } 
    })
    .then(function(stream) {
        video.srcObject = stream;
        
        // Configura o leitor de código de barras (usando a API nativa)
        const barcodeDetector = new BarcodeDetector({ formats: ['qr_code', 'ean_13', 'code_128'] });
        
        // Verifica a cada 500ms por códigos
        const interval = setInterval(() => {
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                barcodeDetector.detect(video)
                    .then(barcodes => {
                        if (barcodes.length > 0) {
                            const codigo = barcodes[0].rawValue;
                            document.querySelector('input[name="codigo"]').value = codigo;
                            fecharCamera(modal, stream);
                            // Submete o formulário automaticamente
                            document.querySelector('form').submit();
                        }
                    })
                    .catch(err => {
                        console.log('Detecção de código falhou:', err);
                    });
            }
        }, 500);
        
        // Função para fechar a câmera
        function fecharCamera(modalElement, stream) {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            clearInterval(interval);
            document.body.removeChild(modalElement);
        }
        
        // Evento do botão fechar
        btnFechar.onclick = () => fecharCamera(modal, stream);
        
        // Fecha ao clicar fora do vídeo
        modal.onclick = (e) => {
            if (e.target === modal) {
                fecharCamera(modal, stream);
            }
        };
        
    })
    .catch(function(err) {
        alert('Erro ao acessar a câmera: ' + err.message);
        document.body.removeChild(modal);
    });
}
</script>
</head>
<body>
    <h1>Sistema de Checklist - Bens Imobilizados</h1>
    
    <!-- Seção de Upload -->
    <div>
        <h3>Upload da Planilha</h3>
        <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data">
            <input type="file" name="planilha" accept=".csv" required>
            <button type="submit">Enviar Planilha</button>
        </form>
    </div>

    <!-- Seção de Busca -->
 <!-- Seção de Busca -->
<div>
    <h3>Checklist de Produtos</h3>
    <form method="post">
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="codigo" placeholder="Digite ou escaneie o código do produto..." required autofocus 
                   style="flex: 1; padding: 10px; font-size: 16px;">
            <button type="button" onclick="abrirCamera()" 
                    style="padding: 10px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;"
                    title="Escanear com Câmera">
                📷
            </button>
            <button type="submit" 
                    style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                Marcar Check
            </button>
        </div>
    </form>
</div>

    <!-- Link para lista de itens -->
    <div>
        <h3>Lista de Itens</h3>
        <a href="lista_itens.php">Ver Lista Completa de Itens</a>
    </div>

    <!-- Estatísticas -->
    <div>
        <h3>Estatísticas</h3>
        <?php include 'estatisticas.php'; ?>
    </div>
</body>
</html>