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
$filtro_status = $_GET['status'] ?? '';

// Construir a query base
$sql = "SELECT p.*, 
               COALESCE(pc.checado, 0) as checado,
               COALESCE(pc.dr, 0) as dr,
               COALESCE(pc.imprimir, 0) as imprimir,
               pc.observacoes,
               pc.nome as nome_editado,
               pc.dependencia as dependencia_editada
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

// Filtro de status
if (!empty($filtro_status)) {
    switch ($filtro_status) {
        case 'checado':
            $sql .= " AND COALESCE(pc.checado, 0) = 1";
            break;
        case 'observacao':
            $sql .= " AND (pc.observacoes IS NOT NULL AND pc.observacoes != '')";
            break;
        case 'etiqueta':
            $sql .= " AND COALESCE(pc.imprimir, 0) = 1";
            break;
        case 'pendente':
            $sql .= " AND (COALESCE(pc.checado, 0) = 0 AND (pc.observacoes IS NULL OR pc.observacoes = '') AND COALESCE(pc.dr, 0) = 0 AND COALESCE(pc.imprimir, 0) = 0)";
            break;
        case 'dr':
            $sql .= " AND COALESCE(pc.dr, 0) = 1";
            break;
    }
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
    }

    .header-btn:hover {
        background-color: rgba(255, 255, 255, 0.2);
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

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        table-layout: fixed;
    }

    th, td {
        padding: 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Centralizar títulos das colunas */
    th {
        text-align: center;
    }

    /* Alinhar código à esquerda e ações à direita */
    td:first-child {
        text-align: left;
    }
    
    td:last-child {
        text-align: right;
    }

    th:nth-child(1),
    td:nth-child(1) {
        width: 40%;
    }

    th:nth-child(2),
    td:nth-child(2) {
        width: 60%;
    }

    .linha-nome td {
        font-size: 12px;
        color: #666;
        white-space: normal;
        text-align: left;
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

    /* NOVAS CORES SIMPLIFICADAS */
    .linha-checado {
        background: #a3fab7ff !important; /* Verde */
    }

    .linha-observacao {
        background: #ffe8a7ff !important; /* Amarelo */
    }

    .linha-imprimir {
        background: #a3d3faff !important; /* Azul */
    }

    .linha-dr {
        background: #faa3a3ff !important; /* Vermelho */
    }

    td form {
        display: inline;
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
    }

    .btn-action:hover {
        opacity: 1;
        border-color: #007bff;
    }

    .btn-action.active {
        opacity: 1;
        border-color: #007bff;
        background: #e9ecef;
    }

    .btn-action.hidden {
        display: none;
    }

    .btn-check.active {
        background: #28a745;
        border-color: #1e7e34;
        color: white;
    }

    .btn-dr.active {
        background: #dc3545;
        border-color: #c82333;
        color: white;
    }

    .btn-imprimir.active {
        background: #007bff;
        border-color: #0056b3;
        color: white;
    }

    /* Botão de observação com fundo amarelo quando tem observação */
    .btn-obs-active {
        background: #ffe8a7ff !important;
        border-color: #ffc107 !important;
    }

    .status-icons {
        display: flex;
        gap: 5px;
        align-items: center;
        margin-top: 5px;
    }

    .status-icon {
        font-size: 14px;
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
        flex-direction: column;
        gap: 5px;
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
        <a href="index.php" class="header-btn" title="Fechar">❌</a>
        <h1 class="header-title"><?php echo htmlspecialchars($planilha['descricao']); ?></h1>
        <div class="header-actions">
            <a href="copiaretiquetas_planilha.php?id=<?php echo $id_planilha; ?>" class="header-btn" title="Copiar Etiquetas">🏷️</a>
            <a href="imprimiralteracao_planilha.php?id=<?php echo $id_planilha; ?>" class="header-btn" title="Imprimir Relatório">🖨️</a>
        </div>
    </header>

    <form method="GET">
        <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
        <input type="text" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>" placeholder="Código...">
        <input type="text" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Nome...">
        <select name="dependencia">
            <option value="">Todas</option>
            <?php foreach ($dependencia_options as $dep): ?>
            <option value="<?php echo htmlspecialchars($dep); ?>" <?php echo $filtro_dependencia===$dep?'selected':''; ?>>
                <?php echo htmlspecialchars($dep); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">Todos Status</option>
            <option value="checado" <?php echo $filtro_status==='checado'?'selected':''; ?>>✅ Checados</option>
            <option value="observacao" <?php echo $filtro_status==='observacao'?'selected':''; ?>>📜 Com Observação</option>
            <option value="etiqueta" <?php echo $filtro_status==='etiqueta'?'selected':''; ?>>🏷️ Etiqueta para Imprimir</option>
            <option value="pendente" <?php echo $filtro_status==='pendente'?'selected':''; ?>>⏳ Pendentes</option>
            <option value="dr" <?php echo $filtro_status==='dr'?'selected':''; ?>>📦 No DR</option>
        </select>
        <button type="submit">🔍 Aplicar Filtros</button>
    </form>

    <!-- Legenda de cores -->
    <div class="legenda">
        <h3>🎨 Legenda de Cores:</h3>
        <div class="legenda-container">
            <div class="legenda-item">
                <div class="legenda-cor" style="background-color: #a3fab7ff;"></div>
                <span>✅ Checado</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor" style="background-color: #ffe8a7ff;"></div>
                <span>📜 Com Observações</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor" style="background-color: #a3d3faff;"></div>
                <span>🏷️ Para Imprimir</span>
            </div>
            <div class="legenda-item">
                <div class="legenda-cor" style="background-color: #faa3a3ff;"></div>
                <span>📦 No DR</span>
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
            // Determinar a classe com base nos status
            $classe = '';
            
            if ($p['dr'] == 1) {
                $classe = 'linha-dr';
            } elseif ($p['imprimir'] == 1 && $p['checado'] == 1) {
                $classe = 'linha-imprimir';
            } elseif ($p['checado'] == 1) {
                $classe = 'linha-checado';
            } elseif (!empty($p['observacoes'])) {
                $classe = 'linha-observacao';
            }
            
            // Determinar quais botões mostrar
            $show_check = ($p['dr'] == 0 && $p['imprimir'] == 0 && empty($p['nome_editado']) && empty($p['dependencia_editada']));
            $show_imprimir = ($p['checado'] == 1 && $p['dr'] == 0 && empty($p['nome_editado']) && empty($p['dependencia_editada']));
            $show_dr = !($p['checado'] == 1 || $p['imprimir'] == 1 || !empty($p['nome_editado']) || !empty($p['dependencia_editada']));
            $show_obs = ($p['dr'] == 0);
            $show_edit = ($p['checado'] == 0 && $p['dr'] == 0);
            
            // Verificar se tem edição
            $tem_edicao = !empty($p['nome_editado']) || !empty($p['dependencia_editada']);
        ?>
        <tr class="<?php echo $classe; ?>">
            <td><?php echo htmlspecialchars($p['codigo']); ?></td>
            <td>
                <div class="acao-container">
                    <!-- Formulário do Checkbox -->
                    <?php if ($show_check): ?>
                    <form method="POST" action="processar_check.php" style="margin: 0; display: inline;">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="checado" value="<?php echo $p['checado'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">
                        <button type="submit" class="btn-action btn-check <?php echo $p['checado'] == 1 ? 'active' : ''; ?>">
                            <?php echo $p['checado'] == 1 ? '✅' : '⬜'; ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Formulário do DR -->
                    <?php if ($show_dr): ?>
                    <form method="POST" action="processar_dr.php" style="margin: 0; display: inline;" onsubmit="return confirmarDR(this, <?php echo $p['dr']; ?>)">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="dr" value="<?php echo $p['dr'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">
                        <button type="submit" class="btn-action btn-dr <?php echo $p['dr'] == 1 ? 'active' : ''; ?>">
                            📦
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Formulário da Impressão -->
                    <?php if ($show_imprimir): ?>
                    <form method="POST" action="processar_etiqueta.php" style="margin: 0; display: inline;" onsubmit="return confirmarImprimir(this, <?php echo $p['imprimir']; ?>)">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="imprimir" value="<?php echo $p['imprimir'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">
                        <button type="submit" class="btn-action btn-imprimir <?php echo $p['imprimir'] == 1 ? 'active' : ''; ?>">
                            🏷️
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Link para Editar Observações -->
                    <?php if ($show_obs): ?>
                    <a href="processar_obs.php?codigo=<?php echo urlencode($p['codigo']); ?>&id_planilha=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"
                       class="btn-action <?php echo !empty($p['observacoes']) ? 'btn-obs-active' : ''; ?>" title="Editar Observações">📜</a>
                    <?php endif; ?>
                    
                    <!-- Link para Editar Produto -->
                    <?php if ($show_edit): ?>
                    <a href="editarproduto_planilha.php?codigo=<?php echo urlencode($p['codigo']); ?>&id_planilha=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"
                       class="btn-action" title="Editar Produto">✍</a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <tr class="linha-nome <?php echo $classe; ?>">
            <td colspan="2">
                <?php if ($tem_edicao): ?>
                    <div style="background: #fff3cd; padding: 5px; margin-bottom: 5px; border-radius: 3px;">
                        <strong>✍ Edição pendente:</strong> 
                        <?php if (!empty($p['nome_editado'])): ?>
                            <strong>Novo nome:</strong> <?php echo htmlspecialchars($p['nome_editado']); ?>
                        <?php endif; ?>
                        <?php if (!empty($p['dependencia_editada'])): ?>
                            <strong>Nova dep.:</strong> <?php echo htmlspecialchars($p['dependencia_editada']); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <strong>Nome:</strong> <?php echo htmlspecialchars($p['nome']); ?><br>
                <?php if (!empty($p['dependencia'])): ?>
                <strong>Dep:</strong> <?php echo htmlspecialchars($p['dependencia']); ?><br>
                <?php endif; ?>
                <?php if (!empty($p['observacoes'])): ?>
                <strong>Obs:</strong> <?php echo htmlspecialchars($p['observacoes']); ?><br>
                <?php endif; ?>
                <div class="status-icons">
                    <?php if ($p['checado'] == 1): ?>
                        <span class="status-icon" title="Produto checado">✅</span>
                    <?php endif; ?>
                    <?php if (!empty($p['observacoes'])): ?>
                        <span class="status-icon" title="Possui observações">📜</span>
                    <?php endif; ?>
                    <?php if ($p['dr'] == 1): ?>
                        <span class="status-icon" title="No DR">📦</span>
                    <?php endif; ?>
                    <?php if ($p['imprimir'] == 1): ?>
                        <span class="status-icon" title="Marcado para impressão">🏷️</span>
                    <?php endif; ?>
                    <?php if ($tem_edicao): ?>
                        <span class="status-icon" title="Produto editado">✍</span>
                    <?php endif; ?>
                    <?php if ($p['checado'] == 0 && empty($p['observacoes']) && $p['dr'] == 0 && $p['imprimir'] == 0 && !$tem_edicao): ?>
                        <span class="status-icon" title="Pendente">⏳</span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
    <p style="text-align: center; margin-top: 20px;">Nenhum produto encontrado.</p>
<?php endif; ?>

    <div class="paginacao">
        <?php 
        // Paginação limitada a 3 páginas
        $inicio = max(1, $pagina - 1);
        $fim = min($total_paginas, $pagina + 1);
        
        // Ajustar para sempre mostrar 3 páginas quando possível
        if ($fim - $inicio < 2) {
            if ($inicio == 1) {
                $fim = min($total_paginas, $inicio + 2);
            } else {
                $inicio = max(1, $fim - 2);
            }
        }
        ?>
        
        <?php if ($pagina > 1): ?>
            <a href="?id=<?php echo $id_planilha; ?>&pagina=1&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>">Início</a>
        <?php endif; ?>

        <?php for ($i = $inicio; $i <= $fim; $i++): ?>
            <?php if ($i == $pagina): ?>
                <strong><?php echo $i; ?></strong>
            <?php else: ?>
                <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $i; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina < $total_paginas): ?>
            <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $total_paginas; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>">Fim</a>
        <?php endif; ?>
    </div>

    <script>
        function confirmarDR(form, drAtual) {
            // Se estiver marcando DR
            if (drAtual == 0) {
                const confirmacao = confirm(
                    'Ao marcar como DR:\n' +
                    '- O campo observação será limpo\n' +
                    '- O produto será desmarcado como checado\n' +
                    '- A etiqueta será desmarcada\n' +
                    '- As edições serão removidas\n\n' +
                    'Deseja continuar?'
                );
                
                if (!confirmacao) {
                    return false;
                }
            }
            return true;
        }

        function confirmarImprimir(form, imprimirAtual) {
            // Se estiver marcando para imprimir
            if (imprimirAtual == 0) {
                if (!confirm('Deseja marcar este produto para impressão de etiqueta?')) {
                    return false;
                }
            }
            return true;
        }
    </script>
</body>
</html>