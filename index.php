<?php
require_once 'conexao.php';

// Parâmetros da paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_descricao = isset($_GET['descricao']) ? $_GET['descricao'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
$exibir_inativos = isset($_GET['exibir_inativos']) ? true : false;

// Construir a query base
$sql = "SELECT * FROM planilhas WHERE 1=1";
$params = [];

// Aplicar filtro de descrição
if (!empty($filtro_descricao)) {
    $sql .= " AND descricao LIKE :descricao";
    $params[':descricao'] = '%' . $filtro_descricao . '%';
}

// Aplicar filtro de status
if (!empty($filtro_status)) {
    $sql .= " AND status = :status";
    $params[':status'] = $filtro_status;
}

// Filtro de ativos/inativos
if (!$exibir_inativos) {
    $sql .= " AND ativo = 1";
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
$sql .= " ORDER BY id DESC LIMIT :limite OFFSET :offset";
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
$planilhas = $stmt->fetchAll();

// Buscar valores únicos de status para o select
$sql_status = "SELECT DISTINCT status FROM planilhas ORDER BY status";
$stmt_status = $conexao->query($sql_status);
$status_options = $stmt_status->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listagem de Planilhas</title>
</head>
<body>
    <h1>Listagem de Planilhas</h1>

    <!-- Botão Importar Planilha -->
    <div style="margin-bottom: 20px;">
        <a href="importar_planilha.php" style="background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;">
            Importar Planilha
        </a>
    </div>

    <!-- Formulário de Filtros -->
    <form method="GET" action="" style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd;">
        <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: end;">
            <!-- Campo de pesquisa por descrição -->
            <div>
                <label for="descricao">Pesquisar por descrição:</label><br>
                <input type="text" id="descricao" name="descricao" value="<?php echo htmlspecialchars($filtro_descricao); ?>" 
                       placeholder="Digite para pesquisar..." style="padding: 8px; width: 250px;">
            </div>

            <!-- Filtro por status -->
            <div>
                <label for="status">Filtrar por status:</label><br>
                <select id="status" name="status" style="padding: 8px;">
                    <option value="">Todos os status</option>
                    <?php foreach ($status_options as $status): ?>
                        <option value="<?php echo $status; ?>" 
                            <?php echo $filtro_status === $status ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Checkbox exibir inativos -->
            <div>
                <label>
                    <input type="checkbox" name="exibir_inativos" value="1" 
                        <?php echo $exibir_inativos ? 'checked' : ''; ?>>
                    Exibir inativos
                </label>
            </div>

            <!-- Botões do formulário -->
            <div>
                <button type="submit" style="padding: 8px 15px; background: #007bff; color: white; border: none; border-radius: 4px;">
                    Aplicar Filtros
                </button>
                <a href="index.php" style="padding: 8px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">
                    Limpar
                </a>
            </div>
        </div>
    </form>

    <!-- Tabela de resultados -->
    <?php if (count($planilhas) > 0): ?>
        <table border="1" cellpadding="10" cellspacing="0" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f8f9fa;">
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($planilhas as $planilha): ?>
                    <tr>
                        <td><?php echo $planilha['id']; ?></td>
                        <td><?php echo htmlspecialchars($planilha['descricao']); ?></td>
                        <td><?php echo ucfirst($planilha['status']); ?></td>
                        <td>
                            <a href="visualizar_planilha.php?id=<?php echo $planilha['id']; ?>" 
                               style="color: #007bff; text-decoration: none; margin-right: 10px;">
                                Visualizar
                            </a>
                            <a href="editar_planilha.php?id=<?php echo $planilha['id']; ?>" 
                               style="color: #28a745; text-decoration: none;">
                                Editar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
            <div style="margin-top: 20px; text-align: center;">
                <?php if ($pagina > 1): ?>
                    <a href="?pagina=<?php echo $pagina - 1; ?>&descricao=<?php echo urlencode($filtro_descricao); ?>&status=<?php echo urlencode($filtro_status); ?>&exibir_inativos=<?php echo $exibir_inativos ? '1' : '0'; ?>" 
                       style="margin-right: 10px; text-decoration: none;">&laquo; Anterior</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <?php if ($i == $pagina): ?>
                        <strong style="margin: 0 5px;"><?php echo $i; ?></strong>
                    <?php else: ?>
                        <a href="?pagina=<?php echo $i; ?>&descricao=<?php echo urlencode($filtro_descricao); ?>&status=<?php echo urlencode($filtro_status); ?>&exibir_inativos=<?php echo $exibir_inativos ? '1' : '0'; ?>" 
                           style="margin: 0 5px; text-decoration: none;"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina + 1; ?>&descricao=<?php echo urlencode($filtro_descricao); ?>&status=<?php echo urlencode($filtro_status); ?>&exibir_inativos=<?php echo $exibir_inativos ? '1' : '0'; ?>" 
                       style="margin-left: 10px; text-decoration: none;">Próxima &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p style="margin-top: 10px;">
            Mostrando <?php echo count($planilhas); ?> de <?php echo $total_registros; ?> registros
        </p>

    <?php else: ?>
        <p>Nenhuma planilha encontrada.</p>
    <?php endif; ?>
</body>
</html>