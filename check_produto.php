<?php
session_start();
require_once 'config.php';
require_once 'planilhaprocessor.php';

// Função para detectar dispositivo
function detectarDispositivo() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Detecta modelo do celular
    $modelo = 'Desconhecido';
    if (preg_match('/iPhone|iPad|iPod/', $user_agent)) {
        $modelo = 'Apple';
    } elseif (preg_match('/Samsung/', $user_agent)) {
        $modelo = 'Samsung';
    } elseif (preg_match('/Motorola/', $user_agent)) {
        $modelo = 'Motorola';
    } elseif (preg_match('/Xiaomi|Redmi/', $user_agent)) {
        $modelo = 'Xiaomi';
    } elseif (preg_match('/Huawei/', $user_agent)) {
        $modelo = 'Huawei';
    }
    
    // Detecta se é mobile
    $tipo = preg_match('/(android|iphone|ipod|mobile)/i', $user_agent) ? 'Mobile' : 'Desktop';
    
    return $modelo . ' - ' . $tipo;
}

// Define usuário
if (!isset($_SESSION['usuario'])) {
    $dispositivo = detectarDispositivo();
    $_SESSION['usuario'] = 'Operador (' . $dispositivo . ')';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $codigo = $_POST['codigo'] ?? '';
    $acao = $_POST['acao'] ?? 'consultar';

    if (empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Código não informado']);
        exit;
    }

    $database = new Database();
    $conn = $database->getConnection();
    $processor = new PlanilhaProcessor($conn);

    try {
        $produto = $processor->buscarPorCodigo($codigo);
        
        if (!$produto) {
            echo json_encode(['success' => false, 'message' => 'Produto não encontrado']);
            exit;
        }
        
        if ($acao === 'marcar') {
            $nome_novo = $_POST['nome_novo'] ?? null;
            $processor->marcarComoChecado($codigo, $nome_novo, $_SESSION['usuario']);
            $produto = $processor->buscarPorCodigo($codigo);
        } elseif ($acao === 'desmarcar') {
            $processor->desmarcarComoChecado($codigo, $_SESSION['usuario']);
            $produto = $processor->buscarPorCodigo($codigo);
        }
        
        echo json_encode([
            'success' => true,
            'produto' => $produto,
            'acao' => $acao
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro no banco de dados: ' . $e->getMessage()]);
    }
    exit;
}

// Se for GET, mostra a página de detalhes
$codigo = $_GET['codigo'] ?? '';
if (empty($codigo)) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();
$processor = new PlanilhaProcessor($conn);
$produto = $processor->buscarPorCodigo($codigo);

if (!$produto) {
    header('Location: index.php?erro=Produto não encontrado');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Produto - <?php echo $produto['codigo']; ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; line-height: 1.6; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 20px; text-align: center; }
        .campo { margin-bottom: 15px; }
        .campo label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; }
        .campo input, .campo textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
        .campo textarea { height: 80px; resize: vertical; }
        .botoes { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px; }
        .btn { padding: 12px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
        .btn-marcar { background: #28a745; color: white; }
        .btn-desmarcar { background: #dc3545; color: white; }
        .btn-voltar { background: #6c757d; color: white; grid-column: 1 / -1; }
        .checado { background: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .nao-checado { background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detalhes do Produto</h1>
        
        <?php if ($produto['checado']): ?>
            <div class="checado">
                <strong>✓ Checado em:</strong> <?php echo date('d/m/Y H:i', strtotime($produto['data_checagem'])); ?><br>
                <strong>Por:</strong> <?php echo htmlspecialchars($produto['usuario_checagem']); ?>
            </div>
        <?php else: ?>
            <div class="nao-checado">
                <strong>✗ Não checado</strong>
            </div>
        <?php endif; ?>
        
        <form id="formProduto">
            <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($produto['codigo']); ?>">
            
            <div class="campo">
                <label>Código:</label>
                <input type="text" value="<?php echo htmlspecialchars($produto['codigo']); ?>" readonly>
            </div>
            
            <div class="campo">
                <label>Nome Original:</label>
                <textarea readonly><?php echo htmlspecialchars($produto['nome'] ?? 'N/A'); ?></textarea>
            </div>
            
            <div class="campo">
                <label>Novo Nome (Editável):</label>
                <textarea name="nome_novo" placeholder="Digite um novo nome se necessário"><?php echo htmlspecialchars($produto['nome_novo'] ?? ''); ?></textarea>
            </div>
            
            <div class="campo">
                <label>Localidade:</label>
                <input type="text" value="<?php echo htmlspecialchars($produto['localidade'] ?? 'N/A'); ?>" readonly>
            </div>
            
            <div class="campo">
                <label>Dependência:</label>
                <input type="text" value="<?php echo htmlspecialchars($produto['dependencia'] ?? 'N/A'); ?>" readonly>
            </div>
            
            <div class="campo">
                <label>Status:</label>
                <input type="text" value="<?php echo htmlspecialchars($produto['status'] ?? 'N/A'); ?>" readonly>
            </div>
            
            <div class="botoes">
                <?php if (!$produto['checado']): ?>
                    <button type="button" onclick="marcarChecado()" class="btn btn-marcar">Marcar como Checado</button>
                <?php else: ?>
                    <button type="button" onclick="desmarcarChecado()" class="btn btn-desmarcar">Desmarcar Checado</button>
                <?php endif; ?>
                <button type="button" onclick="window.history.back()" class="btn btn-voltar">Voltar</button>
            </div>
        </form>
    </div>

    <script>
    function marcarChecado() {
        const formData = new FormData(document.getElementById('formProduto'));
        formData.append('acao', 'marcar');
        
        fetch('check_produto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Produto marcado como checado!');
                window.location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    }
    
    function desmarcarChecado() {
        const formData = new FormData(document.getElementById('formProduto'));
        formData.append('acao', 'desmarcar');
        
        fetch('check_produto.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Checagem removida!');
                window.location.reload();
            } else {
                alert('Erro: ' + data.message);
            }
        });
    }
    </script>
</body>
</html>