<?php
require_once 'conexao.php';

// Verificar e cadastrar palavras com erro do dicionário
try {
    // Buscar todas as palavras com símbolo � em todas as colunas de texto dos produtos
    $colunas_texto = ['nome', 'fornecedor', 'localidade', 'conta', 'numero_documento', 'dependencia', 'observacoes'];
    
    foreach ($colunas_texto as $coluna) {
        $sql_erros = "SELECT DISTINCT $coluna as texto FROM produtos WHERE $coluna LIKE '%�%'";
        $stmt_erros = $conexao->query($sql_erros);
        $textos_com_erro = $stmt_erros->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($textos_com_erro as $texto) {
            // Extrair palavras individuais com �
            preg_match_all('/\b\w*�\w*\b/u', $texto, $palavras_com_erro);
            
            foreach ($palavras_com_erro[0] as $palavra_erro) {
                // Verificar se já existe no dicionário
                $sql_check = "SELECT id FROM dicionario WHERE incorreto = :incorreto";
                $stmt_check = $conexao->prepare($sql_check);
                $stmt_check->bindValue(':incorreto', $palavra_erro);
                $stmt_check->execute();
                
                if (!$stmt_check->fetch()) {
                    // Inserir no dicionário se não existir
                    $sql_insert = "INSERT INTO dicionario (incorreto) VALUES (:incorreto)";
                    $stmt_insert = $conexao->prepare($sql_insert);
                    $stmt_insert->bindValue(':incorreto', $palavra_erro);
                    $stmt_insert->execute();
                }
            }
        }
    }
} catch (Exception $e) {
    error_log("Erro ao verificar palavras com erro: " . $e->getMessage());
}

// Contar palavras pendentes no dicionário
$sql_count_erros = "SELECT COUNT(*) as total FROM dicionario WHERE corrigido IS NULL";
$stmt_count_erros = $conexao->query($sql_count_erros);
$total_erros = $stmt_count_erros->fetch()['total'];

// Parâmetros da paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_descricao = $_GET['descricao'] ?? '';
$mostrar_inativos = isset($_GET['mostrar_inativos']) && $_GET['mostrar_inativos'] == '1';

// Construir a query base
$sql = "SELECT p.*, 
               (SELECT COUNT(*) FROM produtos pr WHERE pr.id_planilha = p.id) as total_produtos,
               (SELECT COUNT(*) FROM produtos pr 
                LEFT JOIN produtos_check pc ON pr.id = pc.produto_id 
                WHERE pr.id_planilha = p.id AND COALESCE(pc.checado, 0) = 1) as checados,
               (SELECT COUNT(*) FROM produtos pr 
                LEFT JOIN produtos_check pc ON pr.id = pc.produto_id 
                WHERE pr.id_planilha = p.id AND (COALESCE(pc.checado, 0) = 0 
                AND (pc.observacoes IS NULL OR pc.observacoes = '') 
                AND COALESCE(pc.dr, 0) = 0 AND COALESCE(pc.imprimir, 0) = 0
                AND (pc.nome IS NULL OR pc.nome= '')
                AND (pc.dependenciaIS NULL OR pc.dependencia= ''))) as pendentes
        FROM planilhas p 
        WHERE 1=1";
$params = [];

// Aplicar filtro de descrição
if (!empty($filtro_descricao)) {
    $sql .= " AND p.descricao LIKE :descricao";
    $params[':descricao'] = '%' . $filtro_descricao . '%';
}

// Filtro ativo/inativo
if ($mostrar_inativos) {
    $sql .= " AND p.ativo = 0";
} else {
    $sql .= " AND p.ativo = 1";
}

// Contar total de registros
$sql_count = "SELECT COUNT(*) as total FROM ($sql) as count_table";
$stmt_count = $conexao->prepare($sql_count);
foreach ($params as $key => $value) {
    $stmt_count->bindValue($key, $value);
}
$stmt_count->execute();
$total_registros = $stmt_count->fetch()['total'];
$total_paginas = ceil($total_registros / $limite);

// Adicionar ordenação e paginação
$sql .= " ORDER BY p.id DESC LIMIT :limite OFFSET :offset";
$params[':limite'] = $limite;
$params[':offset'] = $offset;

// Executar query principal
$stmt = $conexao->prepare($sql);
foreach ($params as $key => $value) {
    if ($key === ':limite' || $key === ':offset') {
        $stmt->bindValue($key, $value, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($key, $value);
    }
}
$stmt->execute();
$planilhas = $stmt->fetchAll();

// Função para determinar status e cor
function getStatusPlanilha($planilha) {
    if ($planilha['total_produtos'] == 0) {
        return ['status' => 'Vazia', 'cor' => '', 'icone' => '📁'];
    } elseif ($planilha['pendentes'] == $planilha['total_produtos']) {
        return ['status' => 'Pendente', 'cor' => 'linha-pendente', 'icone' => '⏳'];
    } elseif ($planilha['checados'] == $planilha['total_produtos']) {
        return ['status' => 'Concluído', 'cor' => 'linha-concluido', 'icone' => '✅'];
    } else {
        return ['status' => 'Em Execução', 'cor' => 'linha-execucao', 'icone' => '🔵'];
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Planilhas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    body {
        font-family: Arial, Helvetica, sans-serif;
        margin: 0;
        padding: 0;
    }

    header {
        background: #007bff;
        padding: 5px 10px;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 50px;
    }

    .header-title {
        width: 50%;
        font-size: 16px;
        margin: 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .header-actions {
        width: 50%;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .header-btn {
        background: none;
        border: none;
        color: #fff;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s;
        text-decoration: none;
        position: relative;
    }

    .header-btn:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    form {
        margin: 10px 0;
        text-align: center;
    }

    form input {
        padding: 8px;
        margin: 5px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    form button {
        padding: 8px 15px;
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        table-layout: fixed;
    }

    th, td {
        padding: 12px 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        border-bottom: 1px solid #ddd;
    }

    th {
        text-align: center;
        background: #007bff;
        color: #fff;
    }

    td:first-child {
        text-align: left;
        width: 50%;
    }
    
    td:nth-child(2) {
        text-align: center;
        width: 30%;
    }

    td:last-child {
        text-align: right;
        width: 20%;
    }

    /* Cores para os status */
    .linha-pendente {
        background: #fff3cd !important;
    }

    .linha-execucao {
        background: #cce7ff !important;
    }

    .linha-concluido {
        background: #d4edda !important;
    }

    .acao-container {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-action {
        background: #f8f9fa;
        border: 2px solid #6c757d;
        cursor: pointer;
        padding: 8px;
        font-size: 18px;
        border-radius: 4px;
        transition: all 0.2s;
        opacity: 0.8;
        text-decoration: none;
        color: #333;
    }

    .btn-action:hover {
        opacity: 1;
        border-color: #007bff;
    }

    .paginacao {
        text-align: center;
        margin: 20px 0;
    }

    .paginacao a,
    .paginacao strong {
        padding: 8px 12px;
        margin: 2px;
        border-radius: 4px;
        text-decoration: none;
    }

    .paginacao a {
        border: 1px solid #ddd;
        color: #007bff;
    }

    .paginacao strong {
        background: #007bff;
        color: #fff;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        margin: 10px 0;
    }

    .checkbox-group input {
        width: auto;
    }

    .legenda {
        background: #f8f9fa;
        padding: 10px;
        margin: 10px 0;
        border-radius: 5px;
        border: 1px solid #dee2e6;
        font-size: 12px;
    }

    .legenda h3 {
        margin: 0 0 8px 0;
        font-size: 14px;
        color: #007bff;
    }

    .legenda-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .legenda-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .legenda-cor {
        width: 15px;
        height: 15px;
        border-radius: 3px;
        border: 1px solid #ccc;
    }
    </style>
</head>
<body>
    <header>
        <a href="importar_planilha.php" class="header-btn" title="Nova Planilha">➕</a>
        <h1 class="header-title">Listagem de Planilhas</h1>
        <div class="header-actions">
            <a href="dicionario_planilha.php" class="header-btn" title="Dicionário">
                📚
                <?php if ($total_erros > 0): ?>
                    <span class="badge"><?php echo $total_erros; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <form method="GET">
        <input type="text" name="descricao" value="<?php echo htmlspecialchars($filtro_descricao); ?>" placeholder="Buscar planilha...">
        <button type="submit">🔍 Buscar</button>
        
        <div class="checkbox-group">
            <input type="checkbox" id="mostrar_inativos" name="mostrar_inativos" value="1" <?php echo $mostrar_inativos ? 'checked' : ''; ?>>
            <label for="mostrar_inativos">Mostrar planilhas inativas</label>
        </div>
    </form>

    <!-- Legenda de cores -->
    <div class="legenda">
        <h3>🎨 Legenda de Status:</h3>
        <div class="legenda-container">
            <div class="legenda-item">
                <div class="legenda-cor" style="background-color: #fff3cd;"></div>
                <span>⏳ Pendente</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor" style="background-color: #cce7ff;"></div>
                <span>🔵 Em Execução</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor" style="background-color: #d4edda;"></div>
                <span>✅ Concluído</span>
            </div>
        </div>
    </div>

    <?php if (count($planilhas) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($planilhas as $planilha): 
                $status_info = getStatusPlanilha($planilha);
            ?>
            <tr class="<?php echo $status_info['cor']; ?>">
                <td><?php echo htmlspecialchars($planilha['descricao']); ?></td>
                <td>
                    <?php echo $status_info['icone']; ?> 
                    <?php echo $status_info['status']; ?>
                    <br>
                    <small>
                        <?php echo $planilha['checados']; ?>/
                        <?php echo $planilha['total_produtos']; ?> produtos
                    </small>
                </td>
                <td>
                    <div class="acao-container">
                        <a href="visualizar_planilha.php?id=<?php echo $planilha['id']; ?>" class="btn-action" title="Visualizar">🔍</a>
                        <a href="editar_planilha.php?id=<?php echo $planilha['id']; ?>" class="btn-action" title="Editar">✍</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_paginas > 1): ?>
    <div class="paginacao">
        <?php 
        $inicio = max(1, $pagina - 1);
        $fim = min($total_paginas, $pagina + 1);
        
        if ($fim - $inicio < 2) {
            if ($inicio == 1) {
                $fim = min($total_paginas, $inicio + 2);
            } else {
                $inicio = max(1, $fim - 2);
            }
        }
        ?>
        
        <?php if ($pagina > 1): ?>
            <a href="?pagina=1&descricao=<?php echo urlencode($filtro_descricao); ?>&mostrar_inativos=<?php echo $mostrar_inativos ? '1' : '0'; ?>">Início</a>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fim; $i++): ?>
            <?php if ($i == $pagina): ?>
                <strong><?php echo $i; ?></strong>
            <?php else: ?>
                <a href="?pagina=<?php echo $i; ?>&descricao=<?php echo urlencode($filtro_descricao); ?>&mostrar_inativos=<?php echo $mostrar_inativos ? '1' : '0'; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina < $total_paginas): ?>
            <a href="?pagina=<?php echo $total_paginas; ?>&descricao=<?php echo urlencode($filtro_descricao); ?>&mostrar_inativos=<?php echo $mostrar_inativos ? '1' : '0'; ?>">Fim</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
        <p style="text-align: center; margin-top: 20px;">Nenhuma planilha encontrada.</p>
    <?php endif; ?>
</body>
</html>