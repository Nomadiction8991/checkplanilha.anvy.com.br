<?php
require_once 'conexao.php';

// Parâmetros da paginação
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Filtros
$filtro_descricao = isset($_GET['descricao']) ? $_GET['descricao'] : '';
$filtro_status = isset($_GET['status']) ? $_GET['status'] : '';
// CORRIGIR: Checkbox marcado = mostrar inativos (ativo = 0)
// Checkbox desmarcado = mostrar ativos (ativo = 1)
$mostrar_inativos = isset($_GET['mostrar_inativos']) && $_GET['mostrar_inativos'] == '1';

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

// CORREÇÃO: Lógica invertida para ativo/inativo
if ($mostrar_inativos) {
    // Checkbox marcado: mostrar apenas inativos (ativo = 0)
    $sql .= " AND ativo = 0";
} else {
    // Checkbox desmarcado: mostrar apenas ativos (ativo = 1) - PADRÃO
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

        header.cabecalho{
            padding: 15px 5px 5px 5px;
            box-shadow: 0px 0px 10px #999;
            position: sticky;
        }
        header.cabecalho div.importar_planilha {
            width: 100%;
            padding: 5px;
            display: inline-block;
            text-align: center;
        }

        header.cabecalho div.importar_planilha a {
            width: auto;
            padding: 5px 10px;
            border-radius: 3px;
            background-color: #28a745;
            text-decoration: none;
            color: #fff;
            margin-block: 5px;
        }

        header.cabecalho div.importar_planilha h1.titulo {
            font-size: 18px;
            display: inline-block;
            color: #333;
        }

        header.cabecalho form.formulario {
            padding: 10px;
        }

        header.cabecalho form.formulario div{
            width: 100%;
            padding: 2.5px 0;
            display: flex;
            flex-direction: row;
            overflow: hidden;
        }

        header.cabecalho form.formulario div input[type="text"]{
            padding: 8px;
            width: 85%;
            margin-inline: auto;
            border:none;
            border: 1px solid #bbb;
            border-radius: 4px 0 0 4px;
            outline: none;
            color: #333;
        }
        header.cabecalho form.formulario div button{
            width: 15%;
            padding: 0 15px;
            background: #007bff;
            color: white;
            border: 1px solid #007bff;
            border-radius: 0 4px 4px 0;
            margin-left: -1px;
        }
        header.cabecalho form.formulario div select{
            padding: 8px;
            border: none;
            border: 1px solid #bbb;
            color: #333;
            border-radius: 4px;
            outline:none;
        }
        header.cabecalho form.formulario div label{
            width: 100%;
            text-align: center;
            align-content: center;

        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* Isso faz a tabela respeitar as larguras */
        }

        table thead tr th {
            padding-block: 5px;
            font-weight: 400;
            color: #fff;
            overflow: hidden;
            background-color: #007bff;
        }

        table tbody tr td {
            color: #333;
            padding: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
table tbody tr td a{
    text-decoration: none;
}
        table tbody tr:nth-child(odd) {
            background-color: #fff; 
        }

        table tbody tr:nth-child(even) {
            background-color: #accff1ff;
        }
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
            min-width: 10px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        table thead tr th:nth-child(3),
        table tbody tr td:nth-child(3) {
            min-width: 80px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        div.paginacao{
            margin-top: 20px;
            text-align: center;
            width: 100%;
        }
        div.paginacao a{
            text-decoration: none;
            color: #333;
            outline: none;
        }
        div.paginacao a{
            color: #007bff;
        }
        p.erro{
            width: 100%;
            margin-block:30px;
            text-align: center;
        }
    </style>
</head>

<body>
    <header class="cabecalho">
        <div class="importar_planilha">
            <a href="importar_planilha.php">+ Nova Planilha</a>
            <h1 class="titulo"> - Listagem de Planilhas</h1>
        </div>
        <form method="GET" class="formulario">
            <div>
                <input type="text" id="descricao" name="descricao" value="<?php echo htmlspecialchars($filtro_descricao); ?>" placeholder="Descrição...">
                <button type="submit">🔍</button>
            </div>
            <div>
                <select id="status" name="status">
                    <option value="">Todos os status</option>
                    <?php foreach ($status_options as $status): ?>
                        <option value="<?php echo $status; ?>"
                            <?php echo $filtro_status === $status ? 'selected' : ''; ?>>
                            <?php echo ucfirst($status); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label>
    <input type="checkbox" name="mostrar_inativos" value="1" <?php echo $mostrar_inativos ? 'checked' : ''; ?>> 
    Mostrar Inativos
</label>
            </div>
        </form>
    </header>
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
                <?php foreach ($planilhas as $planilha): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($planilha['descricao']); ?></td>
                        <td><?php echo ucfirst($planilha['status']); ?></td>
                        <td>
                            <a href="VIEW/view-planilha.php?id=<?php echo $planilha['id']; ?>">
                                🔍
                            </a>
                            <a href="editar_planilha.php?id=<?php echo $planilha['id']; ?>">
                                ✍
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

<?php if ($total_paginas > 1): ?>
    <div class="paginacao">
        <?php if ($pagina > 1): ?>
            <a href="?pagina=<?php echo $pagina - 1; ?>&descricao=<?php echo urlencode($filtro_descricao); ?>&status=<?php echo urlencode($filtro_status); ?>&mostrar_inativos=<?php echo $mostrar_inativos ? '1' : '0'; ?>">&laquo; Anterior </a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <?php if ($i == $pagina): ?>
                <strong style="margin: 0 5px;"><?php echo $i; ?></strong>
            <?php else: ?>
                <a href="?pagina=<?php echo $i; ?>&descricao=<?php echo urlencode($filtro_descricao); ?>&status=<?php echo urlencode($filtro_status); ?>&mostrar_inativos=<?php echo $mostrar_inativos ? '1' : '0'; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($pagina < $total_paginas): ?>
            <a href="?pagina=<?php echo $pagina + 1; ?>&descricao=<?php echo urlencode($filtro_descricao); ?>&status=<?php echo urlencode($filtro_status); ?>&mostrar_inativos=<?php echo $mostrar_inativos ? '1' : '0'; ?>"> Próxima &raquo;</a>
        <?php endif; ?>
    </div>
<?php endif; ?>

    <?php else: ?>
        <p class="erro">Nenhuma planilha encontrada.</p>
    <?php endif; ?>
</body>

</html>