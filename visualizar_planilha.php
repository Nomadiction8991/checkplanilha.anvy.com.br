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
        .btn { padding: 10px 15px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; position: sticky; top: 0; }
        
        .linha-depreciado { background-color: #ffcccc; }
        .linha-com-observacao { background-color: #fff3cd; }
        .linha-checado { background-color: #d4edda; }
        
        .paginacao { text-align: center; margin: 20px 0; }
        .paginacao a, .paginacao strong { padding: 5px 10px; margin: 0 2px; border: 1px solid #ddd; text-decoration: none; }
        .paginacao strong { background: #007bff; color: white; }
        
        .camera-section { background: #e9ecef; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        #cameraPreview { width: 100%; max-width: 300px; height: 200px; border: 1px solid #ccc; margin: 10px 0; }
        .camera-buttons { display: flex; gap: 10px; }
    </style>
</head>
<body>
    <h1>Visualizar Planilha: <?php echo htmlspecialchars($planilha['descricao']); ?></h1>

    <div class="botoes-topo">
        <a href="index.php" class="btn btn-primary">← Voltar para Listagem</a>
        <a href="imprimiralteracao_planilha.php?id=<?php echo $id_planilha; ?>" class="btn btn-warning" target="_blank">Imprimir Alterações</a>
    </div>

    <!-- Seção da Câmera -->
    <div class="camera-section">
        <h3>Scanner de Código</h3>
        <div id="cameraPreview"></div>
        <div class="camera-buttons">
            <button onclick="iniciarCamera()" class="btn btn-primary">Iniciar Câmera</button>
            <button onclick="pararCamera()" class="btn btn-primary">Parar Câmera</button>
        </div>
        <p id="cameraStatus">Câmera não iniciada</p>
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
                    <th>Fornecedor</th>
                    <th>Localidade</th>
                    <th>Conta</th>
                    <th>Nº Documento</th>
                    <th>Dependência</th>
                    <th>Data Aquisição</th>
                    <th>Valor Aquisição</th>
                    <th>Valor Depreciação</th>
                    <th>Valor Atual</th>
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
                        <td><?php echo htmlspecialchars($produto['fornecedor']); ?></td>
                        <td><?php echo htmlspecialchars($produto['localidade']); ?></td>
                        <td><?php echo htmlspecialchars($produto['conta']); ?></td>
                        <td><?php echo htmlspecialchars($produto['numero_documento']); ?></td>
                        <td><?php echo htmlspecialchars($produto['dependencia']); ?></td>
                        <td><?php echo htmlspecialchars($produto['data_aquisicao']); ?></td>
                        <td>R$ <?php echo number_format($produto['valor_aquisicao'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($produto['valor_depreciacao'], 2, ',', '.'); ?></td>
                        <td>R$ <?php echo number_format($produto['valor_atual'], 2, ',', '.'); ?></td>
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
        let stream = null;
        
        function iniciarCamera() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ video: true })
                    .then(function(mediaStream) {
                        stream = mediaStream;
                        const video = document.getElementById('cameraPreview');
                        video.srcObject = mediaStream;
                        video.play();
                        document.getElementById('cameraStatus').textContent = 'Câmera ativa - Aponte para o código de barras';
                    })
                    .catch(function(error) {
                        console.error('Erro ao acessar a câmera:', error);
                        document.getElementById('cameraStatus').textContent = 'Erro ao acessar a câmera: ' + error.message;
                    });
            } else {
                document.getElementById('cameraStatus').textContent = 'Câmera não suportada neste navegador';
            }
        }
        
        function pararCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                document.getElementById('cameraPreview').srcObject = null;
                document.getElementById('cameraStatus').textContent = 'Câmera parada';
            }
        }
        
        // Simulação de leitura de código de barras (para implementação real, use uma biblioteca como QuaggaJS)
        document.getElementById('cameraPreview').addEventListener('click', function() {
            // Esta é uma simulação - na implementação real, você usaria uma biblioteca de leitura de código de barras
            const codigoSimulado = prompt('Digite o código do produto (na implementação real, isso seria lido pela câmera):');
            if (codigoSimulado) {
                window.location.href = 'editar_produto.php?codigo=' + encodeURIComponent(codigoSimulado) + '&id_planilha=<?php echo $id_planilha; ?>';
            }
        });
    </script>
</body>
</html>