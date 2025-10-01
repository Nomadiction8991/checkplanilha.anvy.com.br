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
            border-bottom: 1px solid #888;
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

        /* Modal da Câmera */
        .modal-camera {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #000;
        }

        #barcode-scanner {
            width: 100%;
            height: 300px;
            background: #000;
            border-radius: 5px;
            overflow: hidden;
        }

        .camera-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 4px;
            display: inline-block;
            border: none;
            cursor: pointer;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        #cameraStatus {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }

        .scanner-active #barcode-scanner {
            border: 3px solid #28a745;
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

    <!-- Modal da Câmera -->
    <div id="modalCamera" class="modal-camera">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModalCamera()">&times;</span>
            <h3>Scanner de Código de Barras</h3>
            
            <div id="barcode-scanner"></div>

            <div class="camera-buttons">
                <button onclick="pararScanner()" class="btn btn-danger">
                    <i class="fas fa-stop"></i> Parar Scanner
                </button>
            </div>
            <p id="cameraStatus">Iniciando scanner...</p>
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
                    // Determinar a classe baseada no estado do produto
                    $classe_linha = '';
                    if ($produto['checado'] == 1 && !empty($produto['observacoes'])) {
                        $classe_linha = 'linha-checado-observacao';
                    } elseif ($produto['checado'] == 1) {
                        $classe_linha = 'linha-checado';
                    } elseif (!empty($produto['observacoes'])) {
                        $classe_linha = 'linha-observacao';
                    }
                ?>
                    <!-- Primeira linha: Código, Dependência, Ação -->
                    <tr class="<?php echo $classe_linha; ?>">
                        <td><?php echo htmlspecialchars($produto['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                        <td>
                            <a href="editar_produto.php?codigo=<?php echo urlencode($produto['codigo']); ?>&id_planilha=<?php echo $id_planilha; ?>">
                                ✍
                            </a>
                        </td>
                    </tr>
                    <!-- Segunda linha: Nome do produto -->
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
        const modalCamera = document.getElementById('modalCamera');
        
        function abrirModalCamera() {
            modalCamera.style.display = 'block';
            iniciarScanner();
        }
        
        function fecharModalCamera() {
            modalCamera.style.display = 'none';
            pararScanner();
        }
        
        // Fechar modal clicando fora
        window.onclick = function(event) {
            if (event.target === modalCamera) {
                fecharModalCamera();
            }
        };
        
        function iniciarScanner() {
            if (scannerAtivo) {
                return;
            }
            
            document.getElementById('cameraStatus').textContent = 'Iniciando scanner...';
            document.querySelector('.modal-content').classList.add('scanner-active');
            
            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#barcode-scanner'),
                    constraints: {
                        facingMode: "environment",
                        zoom: 3 // Zoom 3x
                    }
                },
                decoder: {
                    readers: [
                        "code_128_reader",
                        "ean_reader",
                        "ean_8_reader",
                        "code_39_reader",
                        "code_39_vin_reader",
                        "codabar_reader",
                        "upc_reader",
                        "upc_e_reader"
                    ]
                },
                locator: {
                    patchSize: "medium",
                    halfSample: true
                },
                locate: true,
                numOfWorkers: 2
            }, function(err) {
                if (err) {
                    console.error(err);
                    document.getElementById('cameraStatus').textContent = 
                        'Erro ao iniciar scanner: ' + err;
                    return;
                }
                
                Quagga.start();
                scannerAtivo = true;
                document.getElementById('cameraStatus').textContent = 
                    'Scanner ativo - Aponte para o código de barras';
            });
            
            Quagga.onDetected(function(result) {
                const code = result.codeResult.code;
                if (code) {
                    // Parar scanner e redirecionar
                    pararScanner();
                    window.location.href = 'editar_produto.php?codigo=' + 
                        encodeURIComponent(code.trim()) + 
                        '&id_planilha=<?php echo $id_planilha; ?>';
                }
            });
        }
        
        function pararScanner() {
            if (scannerAtivo && Quagga) {
                Quagga.stop();
                scannerAtivo = false;
                document.querySelector('.modal-content').classList.remove('scanner-active');
                document.getElementById('cameraStatus').textContent = 'Scanner parado';
            }
        }
    </script>
</body>
</html>