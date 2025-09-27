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
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
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

if (!empty($filtro_status)) {
    $sql .= " AND p.status LIKE :status";
    $params[':status'] = '%' . $filtro_status . '%';
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
$sql_filtros = "SELECT DISTINCT status, dependencia FROM produtos WHERE id_planilha = :id_planilha ORDER BY status, dependencia";
$stmt_filtros = $conexao->prepare($sql_filtros);
$stmt_filtros->bindValue(':id_planilha', $id_planilha);
$stmt_filtros->execute();
$valores_filtros = $stmt_filtros->fetchAll();

$status_options = array_unique(array_column($valores_filtros, 'status'));
$dependencia_options = array_unique(array_column($valores_filtros, 'dependencia'));
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Planilha - <?php echo htmlspecialchars($planilha['descricao']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .filtro-container { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .filtro-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 10px; }
        .filtro-item label { display: block; margin-bottom: 5px; font-weight: bold; }
        .filtro-item input, .filtro-item select { width: 100%; padding: 8px; box-sizing: border-box; }
        
        .botoes-topo { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block; border: none; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-danger { background: #dc3545; color: white; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; position: sticky; top: 0; }
        
        .linha-depreciado { background-color: #ffcccc; }
        .linha-com-observacao { background-color: #fff3cd; }
        .linha-checado { background-color: #d4edda; }
        
        .paginacao { text-align: center; margin: 20px 0; }
        .paginacao a, .paginacao strong { padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; }
        .paginacao strong { background: #007bff; color: white; }
        
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
        
        #reader {
            width: 100%;
            max-width: 100%;
            margin: 10px 0;
        }
        
        .camera-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 15px;
        }
        
        .btn-scan {
            background: #17a2b8;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        #cameraStatus {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Visualizar Planilha: <?php echo htmlspecialchars($planilha['descricao']); ?></h1>

    <div class="botoes-topo">
        <a href="index.php" class="btn btn-primary">← Voltar para Listagem</a>
        <a href="imprimiralteracao_planilha.php?id=<?php echo $id_planilha; ?>" class="btn btn-warning" target="_blank">Imprimir Alterações</a>
        <button onclick="abrirModalCamera()" class="btn btn-scan">
            <i class="fas fa-camera"></i> Scannear
        </button>
    </div>

    <!-- Modal da Câmera -->
    <div id="modalCamera" class="modal-camera">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModalCamera()">&times;</span>
            <h3>Scanner de Código</h3>
            <div id="reader"></div>
            <div class="camera-buttons">
                <button onclick="iniciarScanner()" class="btn btn-primary">
                    <i class="fas fa-play"></i> Iniciar Scanner
                </button>
                <button onclick="pararScanner()" class="btn btn-danger">
                    <i class="fas fa-stop"></i> Parar Scanner
                </button>
            </div>
            <p id="cameraStatus">Scanner não iniciado</p>
        </div>
    </div>

    <!-- Formulário de Filtros -->
    <div class="filtro-container">
        <h3>Filtrar Produtos</h3>
        <form method="GET" action="">
            <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
            
            <div class="filtro-grid">
                <div class="filtro-item">
                    <label for="codigo">Código:</label>
                    <input type="text" id="codigo" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>" placeholder="Digite o código">
                </div>
                
                <div class="filtro-item">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Digite o nome">
                </div>
                
                <div class="filtro-item">
                    <label for="dependencia">Dependência:</label>
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
                
                <div class="filtro-item">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="">Todos os status</option>
                        <?php foreach ($status_options as $status): ?>
                            <option value="<?php echo htmlspecialchars($status); ?>" 
                                <?php echo $filtro_status === $status ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Aplicar Filtros</button>
            <a href="visualizar_planilha.php?id=<?php echo $id_planilha; ?>" class="btn btn-primary">Limpar Filtros</a>
        </form>
    </div>

    <!-- Tabela de resultados -->
    <?php if (count($produtos) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nome</th>
                    <th>Dependência</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($produtos as $produto): ?>
                    <?php
                    $classe_linha = '';
                    if ($produto['status'] === 'Depreciado') {
                        $classe_linha = 'linha-depreciado';
                    } elseif (!empty($produto['observacoes'])) {
                        $classe_linha = 'linha-com-observacao';
                    } elseif ($produto['checado'] == 1) {
                        $classe_linha = 'linha-checado';
                    }
                    ?>
                    <tr class="<?php echo $classe_linha; ?>">
                        <td><?php echo htmlspecialchars($produto['codigo']); ?></td>
                        <td><?php echo htmlspecialchars($produto['nome']); ?></td>
                        <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                        <td><?php echo htmlspecialchars($produto['status']); ?></td>
                        <td>
                            <a href="editar_produto.php?codigo=<?php echo urlencode($produto['codigo']); ?>&id_planilha=<?php echo $id_planilha; ?>" 
                               class="btn btn-success" style="padding: 5px 10px; font-size: 12px;">
                                Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <div class="paginacao">
                <?php if ($pagina > 1): ?>
                    <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina - 1; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&status=<?php echo urlencode($filtro_status); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>">
                        &laquo; Anterior
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <strong><?php echo $i; ?></strong>
                    <?php else: ?>
                        <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $i; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&status=<?php echo urlencode($filtro_status); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina + 1; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&status=<?php echo urlencode($filtro_status); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>">
                        Próxima &raquo;
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p>Mostrando <?php echo count($produtos); ?> de <?php echo $total_registros; ?> registros</p>

    <?php else: ?>
        <p>Nenhum produto encontrado para esta planilha.</p>
    <?php endif; ?>

    <script>
        let html5QrcodeScanner = null;
        const modalCamera = document.getElementById('modalCamera');
        
        function abrirModalCamera() {
            modalCamera.style.display = 'block';
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
            // Parar scanner anterior se existir
            if (html5QrcodeScanner && html5QrcodeScanner.isScanning) {
                pararScanner();
            }
            
            html5QrcodeScanner = new Html5Qrcode("reader");
            
            Html5Qrcode.getCameras().then(cameras => {
                if (cameras && cameras.length) {
                    const cameraId = cameras[0].id;
                    
                    html5QrcodeScanner.start(
                        cameraId,
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 },
                            supportedScanTypes: [
                                Html5QrcodeScanType.SCAN_TYPE_CAMERA
                            ]
                        },
                        (decodedText) => {
                            // Código escaneado com sucesso - redirecionar para edição
                            window.location.href = 'editar_produto.php?codigo=' + 
                                encodeURIComponent(decodedText) + 
                                '&id_planilha=<?php echo $id_planilha; ?>';
                        },
                        (errorMessage) => {
                            // Ignorar mensagens de erro normais durante a leitura
                            if (!errorMessage.includes('NotFoundException') && 
                                !errorMessage.includes('NoMultiFormatReader')) {
                                console.log('Erro de leitura:', errorMessage);
                            }
                        }
                    ).then(() => {
                        document.getElementById('cameraStatus').textContent = 
                            'Scanner ativo - Aponte para o código de barras';
                    }).catch(err => {
                        document.getElementById('cameraStatus').textContent = 
                            'Erro ao iniciar scanner: ' + err;
                    });
                } else {
                    document.getElementById('cameraStatus').textContent = 
                        'Nenhuma câmera encontrada';
                }
            }).catch(err => {
                document.getElementById('cameraStatus').textContent = 
                    'Erro ao acessar câmeras: ' + err;
            });
        }
        
        function pararScanner() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    html5QrcodeScanner.clear();
                    document.getElementById('cameraStatus').textContent = 'Scanner parado';
                }).catch(err => {
                    console.error("Erro ao parar scanner:", err);
                });
            }
        }
        
        // Parar scanner quando o modal fechar
        modalCamera.addEventListener('hidden', function() {
            pararScanner();
        });
    </script>
</body>
</html>