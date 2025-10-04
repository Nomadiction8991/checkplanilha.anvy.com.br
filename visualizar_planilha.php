<?php
require_once 'conexao.php';

$id_planilha = $_GET['id'] ?? null;

if (!$id_planilha) {
    header('Location: index.php');
    exit;
}

// Verificar se há mensagem de erro
$erro = $_GET['erro'] ?? '';
if (!empty($erro)) {
    echo "<script>alert('" . addslashes($erro) . "');</script>";
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
$filtro_nome = $_GET['nome'] ?? '';
$filtro_dependencia = $_GET['dependencia'] ?? '';
$filtro_codigo = $_GET['codigo'] ?? '';

// Construir a query base - ADICIONADO campo dr
$sql = "SELECT p.*, 
               COALESCE(pc.checado, 0) as checado,
               COALESCE(pc.dr, 0) as dr,
               pc.observacoes
        FROM produtos p 
        LEFT JOIN produtos_check pc ON p.id = pc.produto_id 
        WHERE p.id_planilha = :id_planilha";
$params = [':id_planilha' => $id_planilha];

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

// Contar total
$sql_count = "SELECT COUNT(*) as total FROM ($sql) as count_table";
$stmt_count = $conexao->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $limite);

// Ordenação e paginação
$sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";
$params[':limite'] = $limite;
$params[':offset'] = $offset;

$stmt = $conexao->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, ($key === ':limite' || $key === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$produtos = $stmt->fetchAll();

// Filtros únicos
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
    <script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.20.0"></script>
    <style>
    /* ===== estilo antigo da página ===== */
    body {
        font-family: Arial, Helvetica, sans-serif;
        margin: 0;
        padding: 0;
    }

    header {
        background: #007bff;
        padding: 10px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    header h1 {
        font-size: 18px;
        margin: 0;
    }

    header a {
        color: #fff;
        text-decoration: none;
        font-weight: bold;
    }

    form {
        margin: 10px 0;
        text-align: center;
    }

    form input,
    form select {
        padding: 5px;
        margin: 5px;
    }

    form button {
        padding: 5px 10px;
    }

    /* ===== ATUALIZAÇÃO DAS COLUNAS ===== */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        table-layout: fixed; /* Adiciona layout fixo para controlar larguras */
    }

    th, td {
        padding: 8px;
        text-align: left;
        overflow: hidden; /* Impede que o conteúdo saia da célula */
        text-overflow: ellipsis; /* Adiciona os 3 pontinhos */
        white-space: nowrap; /* Impede quebra de linha */
    }

    /* Coluna Código - 60% */
    th:nth-child(1),
    td:nth-child(1) {
        width: 60%;
    }

    /* Coluna Ação - 40% (restante) */
    th:nth-child(2),
    td:nth-child(2) {
        width: 40%;
    }

    /* Ajuste para a linha do nome que usa colspan - FONTE MENOR */
    .linha-nome td {
        font-size: 12px; /* Fonte menor para o nome */
        color: #666;
    }

    th {
        background: #007bff;
        color: #fff;
        border: 1px solid #014792ff;
    }

    tr:nth-child(even) {
        background: #fff;
        border-bottom: 2px solid #ccc;
    }

    .linha-checado {
        background: #d4edda !important;
    }

    .linha-checado-observacao {
        background: #e6e6fa !important;
    }

    .linha-observacao {
        background: #fff3cd !important;
    }

    .linha-dr {
        background: #f8d7da !important; /* Vermelho claro para DR */
    }

    td form {
        display: inline;
    }

    td form button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 3px;
        transition: background-color 0.2s;
    }

    td form button:hover {
        background-color: #f8f9fa;
    }

    /* Ajuste para os ícones */
    .fa-check-square, .fa-square {
        font-size: 18px;
    }

    /* Container das ações */
    .acao-container {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }

    .paginacao {
        text-align: center;
        margin: 20px 0;
    }

    .paginacao a,
    .paginacao strong {
        padding: 5px 10px;
        margin: 2px;
        border-radius: 3px;
    }

    .paginacao a {
        border: 1px solid #ddd;
        color: #007bff;
        text-decoration: none;
    }

    .paginacao strong {
        background: #007bff;
        color: #fff;
    }

    /* ===== estilo moderno só da câmera ===== */
    .modal-camera {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9)
    }

    .modal-content {
        background: #000;
        margin: 2% auto;
        padding: 0;
        width: 100%;
        height: 96%;
        display: flex;
        flex-direction: column;
        position: relative
    }

    .close-modal {
        position: absolute;
        top: 15px;
        right: 20px;
        color: white;
        font-size: 36px;
        font-weight: bold;
        cursor: pointer;
        z-index: 1001
    }

    #barcode-scanner {
        flex: 1;
        position: relative;
        background: #000
    }

    .scanner-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80%;
        height: 100px;
        border: 2px solid #00ff00;
        background: rgba(0, 255, 0, 0.1);
        pointer-events: none
    }

    .btn-scanner {
        background: #17a2b8;
        color: #fff;
        padding: 8px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        width: fit-content;
        margin: 10px auto
    }

    /* Estilo para o botão DR */
    .btn-dr {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        font-size: 18px;
        border-radius: 3px;
        transition: background-color 0.2s;
    }

    .btn-dr:hover {
        background-color: #f8f9fa;
    }
    </style>
</head>

<body>

    <header>
        <a href="index.php">← Voltar</a>
        <h1><?php echo htmlspecialchars($planilha['descricao']); ?></h1>
        <div style="display: flex; gap: 10px; align-items: center;">
            <button onclick="abrirModalCamera()" class="btn-scanner"><i class="fas fa-camera"></i> Scannear Código</button>
            <a href="imprimiralteracao_planilha.php?id=<?php echo $id_planilha; ?>" 
               style="background: #28a745; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-print"></i> Imprimir Relatório
            </a>
        </div>
    </header>

    <form method="GET">
        <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
        <input type="text" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>"
            placeholder="Código...">
        <input type="text" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Nome...">
        <select name="dependencia">
            <option value="">Todas</option>
            <?php foreach ($dependencia_options as $dep): ?>
            <option value="<?php echo htmlspecialchars($dep); ?>"
                <?php echo $filtro_dependencia===$dep?'selected':''; ?>>
                <?php echo htmlspecialchars($dep); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">🔍</button>
    </form>

    <div id="modalCamera" class="modal-camera">
        <div class="modal-content">
            <span class="close-modal" onclick="fecharModalCamera()">&times;</span>
            <div id="barcode-scanner">
                <div class="scanner-overlay"></div>
            </div>
        </div>
    </div>

   <?php if ($produtos): ?>
<table>
    <thead>
        <tr>
            <th>Código</th>
            <th>Ação</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($produtos as $p): 
            $classe = '';
            if ($p['dr'] == 1) {
                $classe = 'linha-dr';
            } elseif ($p['checado'] == 1 && !empty($p['observacoes'])) {
                $classe = 'linha-checado-observacao';
            } elseif ($p['checado'] == 1) {
                $classe = 'linha-checado';
            } elseif (!empty($p['observacoes'])) {
                $classe = 'linha-observacao';
            }
        ?>
        <tr class="<?php echo $classe; ?>">
            <td><?php echo htmlspecialchars($p['codigo']); ?></td>
            <td style="text-align: center;">
                <div class="acao-container">
                    <!-- Formulário do Checkbox -->
                    <form method="POST" action="processar_check.php" style="margin: 0; display: inline;">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="checado" value="<?php echo $p['checado'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
                        <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0; margin: 0;">
                            <?php if ($p['checado'] == 1): ?>
                                <i class="fas fa-check-square" style="color: #28a745; font-size: 18px;"></i>
                            <?php else: ?>
                                <i class="far fa-square" style="color: #6c757d; font-size: 18px;"></i>
                            <?php endif; ?>
                        </button>
                    </form>
                    
                    <!-- Formulário do DR -->
                    <form method="POST" action="processar_dr.php" style="margin: 0; display: inline;">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="dr" value="<?php echo $p['dr'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
                        <button type="submit" class="btn-dr">
                            📦
                        </button>
                    </form>
                    
                    <!-- Link de Edição -->
                    <a href="editar_produto.php?codigo=<?php echo urlencode($p['codigo']); ?>&id_planilha=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>">
                        ✍
                    </a>
                </div>
            </td>
        </tr>
        <tr class="linha-nome <?php echo $classe; ?>">
            <td colspan="2">
                <strong>Nome: </strong><?php echo htmlspecialchars($p['nome']); ?><br>
                <?php if (!empty($p['dependencia'])): ?>
                <strong>Depa:</strong> <?php echo htmlspecialchars($p['dependencia']); ?><br>
                <?php endif; ?>
                <?php if (!empty($p['observacoes'])): ?>
                <strong>Obse:</strong> <?php echo htmlspecialchars($p['observacoes']); ?>
                <?php endif; ?>
            </td>
            <td></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

    <?php if ($total_paginas>1): ?>
<div class="paginacao">
    <?php if ($pagina>1): ?>
    <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina-1; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>">&laquo; Anterior</a>
    <?php endif; ?>
    
    <?php for ($i=1;$i<=$total_paginas;$i++): ?>
        <?php if ($i==$pagina): ?>
            <strong><?php echo $i; ?></strong>
        <?php else: ?>
            <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $i; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>"><?php echo $i; ?></a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($pagina<$total_paginas): ?>
    <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina+1; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>">Próxima &raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>
 
    <?php else: ?><p style="text-align:center;margin:30px;color:#666">Nenhum produto encontrado.</p><?php endif; ?>

    <script>
    let mediaStream = null,
        scanning = false,
        barcodeDetector = ('BarcodeDetector' in window) ? new BarcodeDetector({
            formats: ['code_128', 'ean_13', 'ean_8', 'code_39', 'upc_a', 'upc_e']
        }) : null,
        videoEl = null;

    async function abrirModalCamera() {
        document.getElementById('modalCamera').style.display = 'block';
        await iniciarCamera();
    }

    function fecharModalCamera() {
        document.getElementById('modalCamera').style.display = 'none';
        pararCamera();
    }

    function pararCamera() {
        if (mediaStream) {
            mediaStream.getTracks().forEach(t => t.stop());
            mediaStream = null;
        }
        scanning = false;
    }

    async function iniciarCamera() {
        if (scanning) return;
        scanning = true;
        videoEl = document.getElementById('videoPreview');
        if (!videoEl) {
            videoEl = document.createElement('video');
            videoEl.id = 'videoPreview';
            videoEl.autoplay = true;
            videoEl.playsInline = true;
            videoEl.muted = true;
            videoEl.style.width = '100%';
            videoEl.style.height = '100%';
            videoEl.style.objectFit = 'cover';
            document.getElementById('barcode-scanner').prepend(videoEl);
        }
        try {
            mediaStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment'
                }
            });
            videoEl.srcObject = mediaStream;
            if (barcodeDetector) {
                detectarNativo();
            } else {
                detectarZXing();
            }
        } catch (e) {
            console.error("Erro ao iniciar câmera", e);
            scanning = false;
        }
    }

    async function detectarNativo() {
        const overlay = document.querySelector('.scanner-overlay');
        const loop = async () => {
            if (!scanning) return;
            try {
                const codes = await barcodeDetector.detect(videoEl);
                if (codes.length > 0) {
                    overlay.style.borderColor = "#0f0";
                    const code = codes[0].rawValue.trim();
                    pararCamera();
                    window.location.href = 'editar_produto.php?codigo=' + encodeURIComponent(code) +
                        '&id_planilha=<?php echo $id_planilha; ?>';
                    return;
                }
            } catch (e) {}
            requestAnimationFrame(loop);
        };
        loop();
    }

    function detectarZXing() {
        const reader = new ZXing.BrowserMultiFormatReader();
        reader.decodeFromVideoDevice(null, videoEl, (result, err) => {
            if (result) {
                document.querySelector('.scanner-overlay').style.borderColor = "#0f0";
                const code = result.text.trim();
                pararCamera();
                reader.reset();
                window.location.href = 'editar_produto.php?codigo=' + encodeURIComponent(code) +
                    '&id_planilha=<?php echo $id_planilha; ?>';
            }
        });
    }
    </script>
</body>
</html>