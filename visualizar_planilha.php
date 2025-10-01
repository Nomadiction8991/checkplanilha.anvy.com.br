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
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>
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

        header.cabecalho form.formulario div label {
            width: auto;
            text-align: center;
            align-content: center;
            white-space: nowrap;
            margin-right: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        header.cabecalho form.formulario div label input[type="checkbox"] {
            margin: 0;
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
            background-color: #e6e6fa !important; /* Roxo claro */
        }

        .linha-observacao {
            background-color: #fff3cd !important; /* Amarelo claro */
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

        /* Modal da Câmera - MELHORADO */
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
            border-radius: 0;
            width: 100%;
            height: 100%;
            max-width: 100%;
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

        #barcode-scanner {
            width: 100%;
            height: 100%;
            background: #000;
            flex: 1;
            position: relative;
            overflow: hidden;
        }

        .scanner-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.8);
            padding: 20px;
            border-radius: 15px;
            display: flex;
            gap: 15px;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .scanner-controls select,
        .scanner-controls input {
            padding: 10px 15px;
            border: 1px solid #666;
            border-radius: 8px;
            background: #222;
            color: white;
            font-size: 14px;
            min-width: 120px;
        }

        .scanner-controls input {
            width: 80px;
        }

        .scanner-controls label {
            color: white;
            font-size: 14px;
            font-weight: bold;
            margin-right: 5px;
        }

        /* Overlay aprimorado */
        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 85%;
            height: 120px;
            border: 3px solid #00ff00;
            background: rgba(0, 255, 0, 0.05);
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

        .scanner-guide {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff00, transparent);
            pointer-events: none;
            z-index: 998;
        }

        .scan-indicator {
            position: absolute;
            top: 50%;
            left: 0;
            width: 100%;
            height: 3px;
            background: #00ff00;
            transform: translateY(-50%);
            opacity: 0;
            z-index: 997;
        }

        .scanning .scan-indicator {
            animation: scan 1.5s ease-in-out infinite;
        }

        .quality-indicator {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.7);
            padding: 10px 15px;
            border-radius: 10px;
            color: white;
            font-size: 14px;
            z-index: 1000;
            display: none;
        }

        .quality-good {
            background: rgba(0, 255, 0, 0.3);
            border: 1px solid #00ff00;
        }

        .quality-poor {
            background: rgba(255, 255, 0, 0.3);
            border: 1px solid #ffff00;
        }

        .quality-bad {
            background: rgba(255, 0, 0, 0.3);
            border: 1px solid #ff0000;
        }

        @keyframes pulse {
            0%, 100% { border-color: #00ff00; }
            50% { border-color: #00cc00; }
        }

        @keyframes scan {
            0% { 
                transform: translateY(-50%) translateX(-100%);
                opacity: 0;
            }
            50% {
                opacity: 1;
            }
            100% { 
                transform: translateY(-50%) translateX(100%);
                opacity: 0;
            }
        }

        .detection-feedback {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(0, 255, 0, 0.3);
            opacity: 0;
            z-index: 996;
            pointer-events: none;
        }

        .detected .detection-feedback {
            animation: detectionPulse 0.5s ease-out;
        }

        @keyframes detectionPulse {
            0% {
                transform: translate(-50%, -50%) scale(0.5);
                opacity: 0.7;
            }
            100% {
                transform: translate(-50%, -50%) scale(2);
                opacity: 0;
            }
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

    <!-- Modal da Câmera MELHORADO -->
    <div id="modalCamera" class="modal-camera">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModalCamera()">&times;</span>
            
            <div id="barcode-scanner">
                <div class="scanner-overlay"></div>
                <div class="scanner-guide"></div>
                <div class="scan-indicator"></div>
                <div class="detection-feedback"></div>
                <div class="quality-indicator" id="qualityIndicator">Qualidade: --</div>
                
                <div class="scanner-controls">
                    <div class="control-group">
                        <label for="cameraSelect">📷</label>
                        <select id="cameraSelect" onchange="trocarCamera()"></select>
                    </div>
                    
                    <div class="control-group">
                        <label for="zoomInput">🔍</label>
                        <input type="number" id="zoomInput" min="1" max="10" step="0.5" value="3" onchange="aplicarZoom()">
                    </div>
                    
                    <div class="control-group">
                        <label for="focusSelect">🎯</label>
                        <select id="focusSelect" onchange="aplicarFoco()">
                            <option value="center">Centralizado</option>
                            <option value="auto">Automático</option>
                            <option value="continuous">Contínuo</option>
                        </select>
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
        let quaggaScanner = null;
        let scannerAtivo = false;
        let camerasDisponiveis = [];
        let cameraAtual = null;
        let detectionCount = 0;
        const modalCamera = document.getElementById('modalCamera');
        
        function abrirModalCamera() {
            modalCamera.style.display = 'block';
            document.body.style.overflow = 'hidden';
            listarCameras().then(() => {
                iniciarScanner();
            });
        }
        
        function fecharModalCamera() {
            modalCamera.style.display = 'none';
            document.body.style.overflow = 'auto';
            pararScanner();
        }
        
        window.onclick = function(event) {
            if (event.target === modalCamera) {
                fecharModalCamera();
            }
        };
        
        // Tecla ESC para fechar
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modalCamera.style.display === 'block') {
                fecharModalCamera();
            }
        });
        
        async function listarCameras() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                camerasDisponiveis = devices.filter(device => device.kind === 'videoinput');
                
                const cameraSelect = document.getElementById('cameraSelect');
                cameraSelect.innerHTML = '';
                
                // Ordenar câmeras: traseira primeiro
                camerasDisponiveis.sort((a, b) => {
                    const aIsBack = a.label.toLowerCase().includes('back') || a.label.toLowerCase().includes('rear');
                    const bIsBack = b.label.toLowerCase().includes('back') || b.label.toLowerCase().includes('rear');
                    return bIsBack - aIsBack;
                });
                
                camerasDisponiveis.forEach((camera, index) => {
                    const option = document.createElement('option');
                    option.value = camera.deviceId;
                    let label = camera.label || `Câmera ${index + 1}`;
                    
                    // Adicionar emoji para identificar tipo de câmera
                    if (label.toLowerCase().includes('back') || label.toLowerCase().includes('rear')) {
                        label = '📷 ' + label;
                    } else if (label.toLowerCase().includes('front') || label.toLowerCase().includes('selfie')) {
                        label = '🤳 ' + label;
                    }
                    
                    option.text = label;
                    cameraSelect.appendChild(option);
                });
                
                if (camerasDisponiveis.length > 0) {
                    cameraAtual = camerasDisponiveis[0].deviceId;
                }
            } catch (err) {
                console.error('Erro ao listar câmeras:', err);
            }
        }
        
        function trocarCamera() {
            const cameraSelect = document.getElementById('cameraSelect');
            cameraAtual = cameraSelect.value;
            reiniciarScanner();
        }
        
        function aplicarZoom() {
            reiniciarScanner();
        }
        
        function aplicarFoco() {
            reiniciarScanner();
        }
        
        function reiniciarScanner() {
            if (scannerAtivo) {
                pararScanner();
                setTimeout(() => iniciarScanner(), 800);
            }
        }
        
        function iniciarScanner() {
            if (scannerAtivo) return;
            
            const zoomInput = document.getElementById('zoomInput');
            const focusSelect = document.getElementById('focusSelect');
            const zoom = parseFloat(zoomInput.value) || 3;
            
            // Configurações otimizadas para códigos do tipo da sua imagem
            const constraints = {
                deviceId: cameraAtual ? { exact: cameraAtual } : undefined,
                video: {
                    width: { ideal: 1920 },
                    height: { ideal: 1080 },
                    frameRate: { ideal: 30 }
                },
                advanced: [
                    { zoom: zoom },
                    { focusMode: focusSelect.value === 'center' ? 'manual' : focusSelect.value }
                ]
            };
            
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#barcode-scanner'),
                    constraints: constraints,
                    area: { // Focar na área central (onde está o overlay)
                        top: "25%",
                        right: "10%", 
                        left: "10%",
                        bottom: "25%"
                    }
                },
                decoder: {
                    readers: [
                        "code_128_reader", // Prioridade para Code 128 (mais comum)
                        "ean_reader",
                        "ean_8_reader", 
                        "code_39_reader",
                        "upc_reader",
                        "upc_e_reader",
                        "codabar_reader"
                    ],
                    multiple: false
                },
                locator: {
                    patchSize: "large", // Maior área de detecção
                    halfSample: false   // Melhor qualidade
                },
                locate: true,
                numOfWorkers: navigator.hardwareConcurrency || 2,
                frequency: 10, // Verificar a cada 10 frames
                debug: {
                    drawBoundingBox: false,
                    showFrequency: false,
                    drawScanline: false,
                    showPattern: false
                }
            }, function(err) {
                if (err) {
                    console.error('Erro ao iniciar scanner:', err);
                    mostrarErroCamera();
                    return;
                }
                
                Quagga.start();
                scannerAtivo = true;
                document.getElementById('barcode-scanner').classList.add('scanning');
                
                // Aplicar configurações avançadas de câmera
                aplicarConfiguracoesCamera();
            });
            
            Quagga.onProcessed(function(result) {
                if (result) {
                    updateQualityIndicator(result);
                }
            });
            
            Quagga.onDetected(function(result) {
                const code = result.codeResult.code;
                if (code && isValidCode(code)) {
                    // Feedback visual de sucesso
                    document.getElementById('barcode-scanner').classList.add('detected');
                    playBeepSound();
                    
                    setTimeout(() => {
                        pararScanner();
                        window.location.href = 'editar_produto.php?codigo=' + 
                            encodeURIComponent(code.trim()) + 
                            '&id_planilha=<?php echo $id_planilha; ?>';
                    }, 300);
                }
            });
        }
        
        function aplicarConfiguracoesCamera() {
            setTimeout(() => {
                try {
                    const track = Quagga.CameraAccess.getActiveTrack();
                    if (track && track.getCapabilities) {
                        const capabilities = track.getCapabilities();
                        
                        // Tentar ajustar foco para modo macro (ideal para códigos próximos)
                        if (capabilities.focusDistance) {
                            track.applyConstraints({
                                advanced: [{ focusMode: 'manual', focusDistance: 0.3 }]
                            });
                        }
                        
                        // Ajustar exposição para melhor leitura
                        if (capabilities.exposureCompensation) {
                            track.applyConstraints({
                                advanced: [{ exposureCompensation: 0.5 }]
                            });
                        }
                    }
                } catch (e) {
                    console.log('Configurações avançadas não suportadas:', e);
                }
            }, 1500);
        }
        
        function updateQualityIndicator(result) {
            const indicator = document.getElementById('qualityIndicator');
            if (!result.box) {
                indicator.style.display = 'none';
                return;
            }
            
            indicator.style.display = 'block';
            const boxSize = Math.sqrt(Math.pow(result.box[1].x - result.box[0].x, 2) + 
                                    Math.pow(result.box[1].y - result.box[0].y, 2));
            
            let quality = 'Boa';
            let qualityClass = 'quality-good';
            
            if (boxSize < 50) {
                quality = 'Baixa';
                qualityClass = 'quality-bad';
            } else if (boxSize < 100) {
                quality = 'Média';
                qualityClass = 'quality-poor';
            }
            
            indicator.textContent = `Qualidade: ${quality}`;
            indicator.className = `quality-indicator ${qualityClass}`;
        }
        
        function isValidCode(code) {
            // Validar formato dos códigos baseado nos exemplos fornecidos
            const patterns = [
                /^\d{2}-\d{4}\s*\/\s*\d{6}$/, // 09-0757 / 000007
                /^\d{2}-\d{4}\s*\/\s*\d{5}$/, // 09-0799 / 00093
                /^\d{2}-\d{4}\s*\/\s*\d{4}$/  // Outros formatos similares
            ];
            
            return patterns.some(pattern => pattern.test(code.trim()));
        }
        
        function playBeepSound() {
            // Criar beep sonoro
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
                // Fallback silencioso se audio não funcionar
                console.log('Audio não suportado');
            }
        }
        
        function mostrarErroCamera() {
            alert('Erro ao acessar a câmera. Verifique as permissões e tente novamente.');
            fecharModalCamera();
        }
        
        function pararScanner() {
            if (scannerAtivo && Quagga) {
                Quagga.stop();
                scannerAtivo = false;
                document.getElementById('barcode-scanner').classList.remove('scanning', 'detected');
                document.getElementById('qualityIndicator').style.display = 'none';
            }
        }
    </script>
</body>
</html>