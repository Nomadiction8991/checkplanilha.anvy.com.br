<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    $_SESSION['usuario'] = 'Operador';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Checklist - Bens Imobilizados</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .usuario { float: right; background: #007bff; color: white; padding: 5px 10px; border-radius: 5px; }
        .section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .section h3 { color: #333; margin-bottom: 15px; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .search-box { display: flex; gap: 10px; margin-bottom: 15px; }
        .search-box input { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-size: 16px; }
        .btn { padding: 12px 20px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-camera { background: #17a2b8; color: white; padding: 12px; }
        .stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .stat-card { background: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; border-left: 4px solid #007bff; }
        .progress { width: 100%; background: #e9ecef; border-radius: 5px; margin: 10px 0; }
        .progress-bar { height: 20px; background: #28a745; border-radius: 5px; }
        @media (max-width: 600px) {
            .stats-grid { grid-template-columns: 1fr; }
            .search-box { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sistema de Checklist - Bens Imobilizados</h1>
            <div class="usuario">Usuário: <?php echo htmlspecialchars($_SESSION['usuario']); ?></div>
            <div style="clear: both;"></div>
        </div>

        <!-- Seção de Upload -->
        <div class="section">
            <h3>Upload da Planilha</h3>
            <form action="upload.php" method="post" enctype="multipart/form-data">
                <input type="file" name="planilha" accept=".csv" required>
                <button type="submit" class="btn btn-primary">Enviar Planilha</button>
            </form>
        </div>

        <!-- Seção de Busca -->
        <div class="section">
            <h3>Checklist de Produtos</h3>
            <form method="get" action="check_produto.php">
                <div class="search-box">
                    <input type="text" name="codigo" placeholder="Digite ou escaneie o código do produto..." required autofocus>
                    <button type="button" onclick="abrirCamera()" class="btn btn-camera" title="Escanear com Câmera">
    📷 Escanear
</button>
                    <button type="submit" class="btn btn-success">Consultar</button>
                </div>
            </form>
        </div>

        <!-- Estatísticas -->
        <div class="section">
            <h3>Estatísticas</h3>
            <?php include 'estatisticas.php'; ?>
        </div>

        <!-- Link para lista de itens -->
        <div class="section">
            <h3>Lista de Itens</h3>
            <a href="lista_itens.php" class="btn btn-primary" style="display: inline-block; text-decoration: none;">Ver Lista Completa</a>
        </div>
    </div>

<script>
function abrirCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Seu navegador não suporta acesso à câmera.');
        return;
    }
    
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.95); z-index: 1000; 
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    `;
    
    const video = document.createElement('video');
    video.style.cssText = `
        width: 90%; max-width: 400px; border: 3px solid white; 
        border-radius: 10px; transform: scaleX(-1); /* Espelha a câmera */
    `;
    video.autoplay = true;
    video.playsInline = true;
    
    const btnFechar = document.createElement('button');
    btnFechar.textContent = '❌ Fechar';
    btnFechar.style.cssText = `
        margin-top: 20px; padding: 12px 24px; 
        background: #dc3545; color: white; border: none; 
        border-radius: 5px; font-size: 16px; cursor: pointer;
    `;
    
    const infoText = document.createElement('div');
    infoText.textContent = 'Aponte a câmera para o código';
    infoText.style.cssText = `
        color: white; margin-bottom: 15px; font-size: 18px; text-align: center;
    `;
    
    modal.appendChild(infoText);
    modal.appendChild(video);
    modal.appendChild(btnFechar);
    document.body.appendChild(modal);
    
    // Tenta usar a API BarcodeDetector se disponível
    let barcodeDetector = null;
    if ('BarcodeDetector' in window) {
        barcodeDetector = new BarcodeDetector({ 
            formats: ['qr_code', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'code_128', 'code_39'] 
        });
    }
    
    let intervalId = null;
    
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'environment',
            width: { ideal: 1280 },
            height: { ideal: 720 }
        } 
    })
    .then(stream => {
        video.srcObject = stream;
        
        if (barcodeDetector) {
            // Escaneamento automático com BarcodeDetector
            intervalId = setInterval(() => {
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    barcodeDetector.detect(video)
                        .then(barcodes => {
                            if (barcodes.length > 0) {
                                const codigo = barcodes[0].rawValue;
                                document.querySelector('input[name="codigo"]').value = codigo;
                                fecharCamera();
                                // Redireciona automaticamente para a página de detalhes
                                window.location.href = `check_produto.php?codigo=${encodeURIComponent(codigo)}`;
                            }
                        })
                        .catch(err => {
                            console.log('Erro na detecção:', err);
                        });
                }
            }, 1000); // Verifica a cada 1 segundo
        }
        
        // Alternativa: captura manual ao tocar na tela
        video.onclick = () => {
            // Para dispositivos sem BarcodeDetector, permite digitação manual
            fecharCamera();
            document.querySelector('input[name="codigo"]').focus();
        };
        
        function fecharCamera() {
            if (intervalId) clearInterval(intervalId);
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            document.body.removeChild(modal);
        }
        
        btnFechar.onclick = fecharCamera;
        modal.onclick = (e) => { 
            if (e.target === modal) fecharCamera(); 
        };
        
    })
    .catch(err => {
        alert('Erro ao acessar a câmera: ' + err.message);
        document.body.removeChild(modal);
    });
}

// Função alternativa para dispositivos mais antigos
function abrirCameraSimples() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Seu navegador não suporta câmera. Digite o código manualmente.');
        return;
    }
    
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: black; z-index: 1000; display: flex; flex-direction: column;
    `;
    
    const video = document.createElement('video');
    video.style.cssText = `width: 100%; height: 100%; object-fit: cover;`;
    video.autoplay = true;
    video.playsInline = true;
    
    const btnContainer = document.createElement('div');
    btnContainer.style.cssText = `
        position: absolute; bottom: 20px; left: 0; width: 100%; 
        text-align: center; padding: 20px;
    `;
    
    const btnCapturar = document.createElement('button');
    btnCapturar.textContent = '📷 Usar esta imagem';
    btnCapturar.style.cssText = `
        padding: 15px 25px; background: #28a745; color: white; 
        border: none; border-radius: 50px; font-size: 18px; cursor: pointer;
    `;
    
    const btnFechar = document.createElement('button');
    btnFechar.textContent = 'Fechar';
    btnFechar.style.cssText = `
        margin-top: 10px; padding: 10px 20px; background: #dc3545; 
        color: white; border: none; border-radius: 5px; cursor: pointer;
    `;
    
    btnContainer.appendChild(btnCapturar);
    btnContainer.appendChild(btnFechar);
    modal.appendChild(video);
    modal.appendChild(btnContainer);
    document.body.appendChild(modal);
    
    navigator.mediaDevices.getUserMedia({ 
        video: { facingMode: 'environment' } 
    })
    .then(stream => {
        video.srcObject = stream;
        
        btnCapturar.onclick = () => {
            fecharCamera();
            document.querySelector('input[name="codigo"]').focus();
            alert('Agora digite o código manualmente no campo.');
        };
        
        function fecharCamera() {
            stream.getTracks().forEach(track => track.stop());
            document.body.removeChild(modal);
        }
        
        btnFechar.onclick = fecharCamera;
    })
    .catch(err => {
        alert('Não foi possível acessar a câmera: ' + err.message);
        document.body.removeChild(modal);
    });
}
</script>
</body>
</html>