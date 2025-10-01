<?php
require_once 'conexao.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: index.php');
    exit;
}

// Buscar dados da planilha
try {
    $sql_planilha = "SELECT * FROM planilhas WHERE id = :id";
    $stmt_planilha = $conexao->prepare($sql_planilha);
    $stmt_planilha->bindValue(':id', $id_planilha);
    $stmt_planilha->execute();
    $planilha = $stmt_planilha->fetch();
    
    if (!$planilha) {
        throw new Exception('Planilha não encontrada.');
    }
} catch (Exception $e) {
    die("Erro ao carregar planilha: " . $e->getMessage());
}

// Parâmetros da paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 50;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_nome = isset($_GET['nome']) ? $_GET['nome'] : '';
$filtro_dependencia = isset($_GET['dependencia']) ? $_GET['dependencia'] : '';
$filtro_codigo = isset($_GET['codigo']) ? $_GET['codigo'] : '';

// Construir a query base
$sql = "SELECT p.*, 
               COALESCE(pc.checado, 0) as checado,
               pc.observacoes
        FROM produtos p 
        LEFT JOIN produtos_check pc ON p.id = pc.produto_id 
        WHERE p.id_planilha = :id_planilha";
$params = [':id_planilha' => $id_planilha];

// Aplicar filtros
if (!empty($filtro_nome)) {
    $sql .= " AND p.nome LIKE :nome";
    $params[':nome'] = '%' . $filtro_nome . '%';
}

if (!empty($filtro_dependencia)) {
    $sql .= " AND p.dependencia LIKE :dependencia";
    $params[':dependencia'] = '%' . $filtro_dependencia . '%';
}

if (!empty($filtro_codigo)) {
    $sql .= " AND p.codigo LIKE :codigo";
    $params[':codigo'] = '%' . $filtro_codigo . '%';
}

// Contar total de registros (para paginação)
$sql_count = "SELECT COUNT(*) as total FROM ($sql) as count_table";
$stmt_count = $conexao->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $limite);

// Adicionar ordenação e paginação à query principal
$sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";
$params[':limite'] = $limite;
$params[':offset'] = $offset;

// Executar a query principal
$stmt = $conexao->prepare($sql);
foreach ($params as $key => $value) {
    if ($key === ':limite' || $key === ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$produtos = $stmt->fetchAll();

// Buscar valores únicos para os filtros
$sql_filtros = "SELECT DISTINCT dependencia FROM produtos WHERE id_planilha = :id_planilha ORDER BY dependencia";
$stmt_filtros = $conexao->prepare($sql_filtros);
$stmt_filtros->bindValue(':id_planilha', $id_planilha);
$stmt_filtros->execute();
$dependencia_options = $stmt_filtros->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Planilha - <?php echo htmlspecialchars($planilha['descricao']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/dynamsoft-javascript-barcode@9.6.30/dist/dbr.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            box-sizing: border-box;
        }

        body {
            display: flex;
            flex-direction: column;
            width: 100vw;
        }

        header.cabecalho {
            padding: 15px 5px 5px 5px;
            box-shadow: 0px 0px 10px #999;
            position: sticky;
            top: 0;
            background: white;
            z-index: 100;
        }

        header.cabecalho div.titulo_container {
            width: 100%;
            padding: 5px;
            display: inline-block;
            text-align: center;
        }

        header.cabecalho div.titulo_container a {
            width: auto;
            padding: 5px 10px;
            border-radius: 3px;
            background-color: #28a745;
            text-decoration: none;
            color: #fff;
            margin-block: 5px;
            display: inline-block;
        }

        header.cabecalho div.titulo_container h1.titulo {
            font-size: 18px;
            display: inline-block;
            color: #333;
            margin-left: 10px;
        }

        header.cabecalho form.formulario {
            padding: 10px;
        }

        header.cabecalho form.formulario div {
            width: 100%;
            padding: 2.5px 0;
            display: flex;
            flex-direction: row;
            overflow: hidden;
        }

        header.cabecalho form.formulario div input[type="text"] {
            padding: 8px;
            width: 85%;
            margin-inline: auto;
            border: none;
            border: 1px solid #bbb;
            border-radius: 4px 0 0 4px;
            outline: none;
            color: #333;
        }

        header.cabecalho form.formulario div button {
            width: 15%;
            padding: 0 15px;
            background: #007bff;
            color: white;
            border: 1px solid #007bff;
            border-radius: 0 4px 4px 0;
            margin-left: -1px;
            cursor: pointer;
        }

        header.cabecalho form.formulario div select {
            padding: 8px;
            border: none;
            border: 1px solid #bbb;
            color: #333;
            border-radius: 4px;
            outline: none;
            flex: 1;
            margin-right: 10px;
        }

        .btn-scanner {
            background: #17a2b8;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            width: fit-content;
            margin: 10px auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table thead tr th {
            padding-block: 8px;
            font-weight: 400;
            color: #fff;
            overflow: hidden;
            background-color: #007bff;
            text-align: left;
            padding-left: 8px;
        }

        table tbody tr td {
            color: #333;
            padding: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            border-bottom: 1px solid #e0e0e0;
        }

        table tbody tr td a {
            text-decoration: none;
            color: #007bff;
            padding: 4px 8px;
            border-radius: 3px;
            transition: background-color 0.2s;
        }

        table tbody tr td a:hover {
            background-color: rgba(0, 123, 255, 0.1);
        }

        table tbody tr:nth-child(4n+1),
        table tbody tr:nth-child(4n+2) {
            background-color: #fff;
        }

        table tbody tr:nth-child(4n+3),
        table tbody tr:nth-child(4n+4) {
            background-color: #f8f9fa;
        }

        /* Larguras das colunas */
        table thead tr th:nth-child(1),
        table tbody tr td:nth-child(1) {
            width: 50%;
            max-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        table thead tr th:nth-child(2),
        table tbody tr td:nth-child(2) {
            width: 35%;
            max-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        table thead tr th:nth-child(3),
        table tbody tr td:nth-child(3) {
            width: 15%;
            max-width: 0;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Estilo para a segunda linha (nome do produto) */
        .linha-nome {
            background-color: #fff !important;
        }

        .linha-nome td {
            padding: 8px;
            color: #333;
            white-space: normal;
            line-height: 1.4;
            max-height: none;
            overflow: visible;
            text-overflow: unset;
            border-bottom: 1px solid #e0e0e0;
        }

        /* Cores para estados dos produtos */
        .linha-checado {
            background-color: #d4edda !important;
        }

        .linha-checado-observacao {
            background-color: #e6e6fa !important;
        }

        .linha-observacao {
            background-color: #fff3cd !important;
        }

        div.paginacao {
            margin-top: 20px;
            text-align: center;
            width: 100%;
            padding: 10px;
        }

        div.paginacao a {
            text-decoration: none;
            color: #007bff;
            padding: 5px 10px;
            margin: 0 2px;
            border: 1px solid #ddd;
            border-radius: 3px;
            display: inline-block;
        }

        div.paginacao strong {
            padding: 5px 10px;
            margin: 0 2px;
            background: #007bff;
            color: white;
            border: 1px solid #007bff;
            border-radius: 3px;
            display: inline-block;
        }

        p.erro {
            width: 100%;
            margin-block: 30px;
            text-align: center;
            color: #666;
        }

        /* Modal da Câmera - PROFISSIONAL */
        .modal-camera {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.95);
        }

        .modal-content {
            background-color: #000;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 36px;
            font-weight: bold;
            cursor: pointer;
            z-index: 1001;
            background: rgba(0,0,0,0.6);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid white;
        }

        .close-modal:hover {
            background: rgba(255,0,0,0.7);
        }

        #scanner-container {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        #reader {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 150px;
            border: 3px solid #00ff00;
            background: rgba(0, 255, 0, 0.1);
            pointer-events: none;
            z-index: 999;
            border-radius: 15px;
            box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.7);
            animation: pulse 2s infinite;
        }

        .scanner-overlay::before {
            content: 'Posicione o código na área verde';
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            color: #00ff00;
            font-size: 16px;
            font-weight: bold;
            white-space: nowrap;
            text-shadow: 0 0 10px rgba(0, 255, 0, 0.8);
        }

        .scanner-controls {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            padding: 15px;
            border-radius: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            width: 90%;
            max-width: 400px;
        }

        .control-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            gap: 10px;
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
        }

        .control-group label {
            color: white;
            font-size: 16px;
            font-weight: bold;
            min-width: 30px;
            text-align: center;
        }

        .scanner-controls select,
        .scanner-controls input {
            padding: 10px 12px;
            border: 1px solid #666;
            border-radius: 8px;
            background: #222;
            color: white;
            font-size: 14px;
            flex: 1;
        }

        .scanner-controls input[type="range"] {
            flex: 2;
        }

        .zoom-value {
            color: white;
            min-width: 40px;
            text-align: center;
            font-weight: bold;
        }

        .scan-line {
            position: absolute;
            top: 50%;
            left: 10%;
            width: 80%;
            height: 3px;
            background: linear-gradient(90deg, transparent, #00ff00, transparent);
            transform: translateY(-50%);
            animation: scan 2s ease-in-out infinite;
            z-index: 998;
        }

        .status-indicator {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            padding: 10px 15px;
            border-radius: 10px;
            color: white;
            font-size: 14px;
            z-index: 1000;
        }

        @keyframes pulse {
            0%, 100% { border-color: #00ff00; }
            50% { border-color: #00cc00; }
        }

        @keyframes scan {
            0% { transform: translateY(-50%) translateX(-100%); opacity: 0; }
            50% { opacity: 1; }
            100% { transform: translateY(-50%) translateX(100%); opacity: 0; }
        }
    </style>
</head>
<body>
    <header class="cabecalho">
        <div class="titulo_container">
            <a href="index.php">← Voltar</a>
            <h1 class="titulo"><?php echo htmlspecialchars($planilha['descricao']); ?></h1>
        </div>
        
        <button onclick="abrirModalCamera()" class="btn-scanner">
            <i class="fas fa-camera"></i> Scannear Código
        </button>

        <form method="GET" class="formulario">
            <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
            
            <div>
                <input type="text" id="codigo" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>" placeholder="Código...">
                <button type="submit">🔍</button>
            </div>
            
            <div>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Nome...">
                <select id="dependencia" name="dependencia">
                    <option value="">Todas as dependências</option>
                    <?php foreach ($dependencia_options as $dependencia): ?>
                        <option value="<?php echo htmlspecialchars($dependencia); ?>" 
                            <?php echo $filtro_dependencia === $dependencia ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($dependencia); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </header>

    <!-- Modal da Câmera PROFISSIONAL -->
    <div id="modalCamera" class="modal-camera">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModalCamera()">&times;</span>
            
            <div id="scanner-container">
                <div id="reader"></div>
                <div class="scanner-overlay"></div>
                <div class="scan-line"></div>
                <div class="status-indicator" id="statusIndicator">Preparando câmera...</div>
                
                <div class="scanner-controls">
                    <!-- Câmera -->
                    <div class="control-row">
                        <div class="control-group">
                            <label for="cameraSelect">📷</label>
                            <select id="cameraSelect" onchange="trocarCamera()"></select>
                        </div>
                    </div>
                    
                    <!-- Zoom -->
                    <div class="control-row">
                        <div class="control-group">
                            <label for="zoomSlider">🔍</label>
                            <input type="range" id="zoomSlider" min="100" max="500" value="200" onchange="atualizarZoom()">
                            <span id="zoomDisplay" class="zoom-value">2.0x</span>
                        </div>
                    </div>
                    
                    <!-- Flash -->
                    <div class="control-row">
                        <div class="control-group">
                            <label for="flashToggle">💡</label>
                            <select id="flashToggle" onchange="toggleFlash()">
                                <option value="off">Flash Off</option>
                                <option value="on">Flash On</option>
                                <option value="auto">Auto</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($produtos) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Dependência</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $produto): 
                    $classe_linha = '';
                    if ($produto['checado'] == 1 && !empty($produto['observacoes'])) {
                        $classe_linha = 'linha-checado-observacao';
                    } elseif ($produto['checado'] == 1) {
                        $classe_linha = 'linha-checado';
                    } elseif (!empty($produto['observacoes'])) {
                        $classe_linha = 'linha-observacao';
                    }
                ?>
                    <tr class="<?php echo $classe_linha; ?>">
                        <td><?php echo htmlspecialchars($produto['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                        <td>
                            <a href="editar_produto.php?codigo=<?php echo urlencode($produto['codigo']); ?>&id_planilha=<?php echo $id_planilha; ?>">
                                ✍
                            </a>
                        </td>
                    </tr>
                    <tr class="linha-nome <?php echo $classe_linha; ?>">
                        <td colspan="3">
                            <?php echo htmlspecialchars($produto['nome']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_paginas > 1): ?>
            <div class="paginacao">
                <?php if ($pagina > 1): ?>
                    <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina - 1; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>">
                        &laquo; Anterior
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <strong><?php echo $i; ?></strong>
                    <?php else: ?>
                        <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $i; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina + 1; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>">
                        Próxima &raquo;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <p class="erro">Nenhum produto encontrado.</p>
    <?php endif; ?>

    <script>
        // Configuração do Dynamsoft Barcode Reader
        Dynamsoft.DBR.BarcodeReader.license = 'DLS2eyJvcmdhbml6YXRpb25JRCI6IjIwMDAwMSJ9';
        
        let scanner = null;
        let currentCamera = null;
        let cameras = [];
        const modalCamera = document.getElementById('modalCamera');
        
        // Inicializar o scanner
        async function initScanner() {
            try {
                scanner = await Dynamsoft.DBR.BarcodeScanner.createInstance();
                
                // Configurações otimizadas para códigos pequenos
                await scanner.updateRuntimeSettings("speed");
                
                // Configurar quais códigos ler
                let settings = await scanner.getRuntimeSettings();
                settings.barcodeFormatIds = 
                    Dynamsoft.DBR.EnumBarcodeFormat.BF_CODE_39 |
                    Dynamsoft.DBR.EnumBarcodeFormat.BF_CODE_128 |
                    Dynamsoft.DBR.EnumBarcodeFormat.BF_EAN_13 |
                    Dynamsoft.DBR.EnumBarcodeFormat.BF_EAN_8 |
                    Dynamsoft.DBR.EnumBarcodeFormat.BF_CODE_93 |
                    Dynamsoft.DBR.EnumBarcodeFormat.BF_CODABAR;
                
                settings.expectedBarcodesCount = 1;
                await scanner.updateRuntimeSettings(settings);
                
                // Definir callbacks
                scanner.onFrameRead = results => {
                    if (results.length > 0) {
                        handleBarcodeDetected(results[0].barcodeText);
                    }
                };
                
                scanner.onUniqueRead = (txt, result) => {
                    handleBarcodeDetected(txt);
                };
                
                console.log('Scanner inicializado com sucesso');
                
            } catch (ex) {
                console.error('Erro ao inicializar scanner:', ex);
                document.getElementById('statusIndicator').textContent = 'Erro: ' + ex.message;
            }
        }
        
        // Abrir modal da câmera
        async function abrirModalCamera() {
            modalCamera.style.display = 'block';
            document.body.style.overflow = 'hidden';
            document.getElementById('statusIndicator').textContent = 'Iniciando câmera...';
            
            if (!scanner) {
                await initScanner();
            }
            
            await listarCameras();
            await iniciarCamera();
        }
        
        // Fechar modal
        function fecharModalCamera() {
            modalCamera.style.display = 'none';
            document.body.style.overflow = 'auto';
            pararCamera();
        }
        
        // Listar câmeras disponíveis
        async function listarCameras() {
            try {
                cameras = await scanner.getAllCameras();
                const cameraSelect = document.getElementById('cameraSelect');
                cameraSelect.innerHTML = '';
                
                cameras.forEach((camera, index) => {
                    const option = document.createElement('option');
                    option.value = camera.deviceId;
                    let label = camera.label || `Câmera ${index + 1}`;
                    
                    // Identificar tipo de câmera
                    if (label.toLowerCase().includes('back') || label.toLowerCase().includes('rear')) {
                        label = '📷 ' + label;
                    } else if (label.toLowerCase().includes('front') || label.toLowerCase().includes('selfie')) {
                        label = '🤳 ' + label;
                    }
                    
                    option.text = label.length > 30 ? label.substring(0, 30) + '...' : label;
                    cameraSelect.appendChild(option);
                });
                
                if (cameras.length > 0) {
                    currentCamera = cameras[0].deviceId;
                }
                
            } catch (ex) {
                console.error('Erro ao listar câmeras:', ex);
            }
        }
        
        // Iniciar câmera
        async function iniciarCamera() {
            try {
                if (scanner) {
                    await scanner.setCurrentCamera(currentCamera);
                    await scanner.show();
                    
                    // Aplicar zoom inicial
                    aplicarZoom(2.0);
                    
                    document.getElementById('statusIndicator').textContent = 'Camera pronta - Aponte para o código';
                    document.getElementById('statusIndicator').style.background = 'rgba(0, 255, 0, 0.3)';
                    
                }
            } catch (ex) {
                console.error('Erro ao iniciar câmera:', ex);
                document.getElementById('statusIndicator').textContent = 'Erro: ' + ex.message;
                document.getElementById('statusIndicator').style.background = 'rgba(255, 0, 0, 0.3)';
            }
        }
        
        // Parar câmera
        function pararCamera() {
            if (scanner) {
                scanner.hide();
            }
        }
        
        // Trocar câmera
        async function trocarCamera() {
            const cameraSelect = document.getElementById('cameraSelect');
            currentCamera = cameraSelect.value;
            await iniciarCamera();
        }
        
        // Aplicar zoom
        function aplicarZoom(zoomLevel) {
            const videoElement = document.querySelector('#reader video');
            if (videoElement) {
                // Dynamsoft tem controle nativo de zoom
                try {
                    const track = videoElement.srcObject.getVideoTracks()[0];
                    const capabilities = track.getCapabilities();
                    
                    if (capabilities.zoom) {
                        const zoom = capabilities.zoom.min + (capabilities.zoom.max - capabilities.zoom.min) * (zoomLevel - 1) / 4;
                        track.applyConstraints({
                            advanced: [{ zoom: Math.min(zoom, capabilities.zoom.max) }]
                        });
                    }
                } catch (ex) {
                    console.log('Zoom não suportado:', ex);
                }
            }
        }
        
        // Atualizar zoom pelo slider
        function atualizarZoom() {
            const zoomSlider = document.getElementById('zoomSlider');
            const zoomDisplay = document.getElementById('zoomDisplay');
            const zoomLevel = zoomSlider.value / 100;
            
            zoomDisplay.textContent = zoomLevel.toFixed(1) + 'x';
            aplicarZoom(zoomLevel);
        }
        
        // Alternar flash
        async function toggleFlash() {
            const flashToggle = document.getElementById('flashToggle');
            // Implementação do flash depende do dispositivo
            console.log('Flash:', flashToggle.value);
        }
        
        // Processar código detectado
        function handleBarcodeDetected(barcodeText) {
            if (isValidCode(barcodeText)) {
                // Feedback visual e sonoro
                document.getElementById('statusIndicator').textContent = '✓ Código lido!';
                document.getElementById('statusIndicator').style.background = 'rgba(0, 255, 0, 0.5)';
                
                playBeepSound();
                
                // Redirecionar após breve delay
                setTimeout(() => {
                    pararCamera();
                    window.location.href = 'editar_produto.php?codigo=' + 
                        encodeURIComponent(barcodeText.trim()) + 
                        '&id_planilha=<?php echo $id_planilha; ?>';
                }, 500);
            }
        }
        
        // Validar formato do código
        function isValidCode(code) {
            const patterns = [
                /^\d{2}-\d{4}\s*\/\s*\d{6}$/, // 09-0757 / 000007
                /^\d{2}-\d{4}\s*\/\s*\d{5}$/, // 09-0799 / 00093
                /^\d{2}-\d{4}\s*\/\s*\d{4}$/  // Outros formatos
            ];
            
            return patterns.some(pattern => pattern.test(code.trim()));
        }
        
        // Som de confirmação
        function playBeepSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
                
                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.2);
            } catch (e) {
                console.log('Audio não suportado');
            }
        }
        
        // Event listeners
        window.onclick = function(event) {
            if (event.target === modalCamera) {
                fecharModalCamera();
            }
        };
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modalCamera.style.display === 'block') {
                fecharModalCamera();
            }
        });
        
        // Inicializar quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            initScanner();
        });
    </script>
</body>
</html>