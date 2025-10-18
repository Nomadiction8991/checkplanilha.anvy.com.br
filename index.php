<?php
require_once 'CRUD/READ/index.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anvy - Listagem de Planilhas</title>
    <link rel="stylesheet" href="STYLE/index.css">
    <!-- Meta tags PWA -->
    <meta name="theme-color" content="#fff"/>
    <link rel="manifest" href="manifest.json">


</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <h1>Anvy</h1>
        </section>
        <section class="acoes">
            <a href="VIEW/importar-planilha.php">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF">
                    <path d="M440-440H200v-80h240v-240h80v240h240v80H520v240h-80v-240Z"/>
                </svg>
            </a>
        </section>
    </header>

    <!-- Seção de Pesquisa -->
    <section class="pesquisa-container">
        <form method="GET" class="form-pesquisa">
            <div class="campo-comum">
                <input type="text" id="comum" name="comum" value="<?php echo htmlspecialchars($filtro_comum ?? ''); ?>" placeholder="Pesquisar comum...">
                <button type="submit" class="btn-filtrar" title="Filtrar">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                    </svg>
                </button>
            </div>

            <details class="filtros-avancados">
                <summary>Filtros Avançados</summary>
                <div class="filtros-content">
                    <div class="campo-pesquisa">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Todos os status</option>
                            <?php foreach ($status_options as $status): ?>
                                <option value="<?php echo $status; ?>"
                                    <?php echo ($filtro_status ?? '') === $status ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="campo-pesquisa">
                        <label for="ativo">Exibir</label>
                        <select id="ativo" name="ativo">
                            <option value="1" <?php echo ($filtro_ativo ?? '1') === '1' ? 'selected' : ''; ?>>Apenas Ativos</option>
                            <option value="0" <?php echo ($filtro_ativo ?? '1') === '0' ? 'selected' : ''; ?>>Apenas Inativos</option>
                            <option value="todos" <?php echo ($filtro_ativo ?? '1') === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        </select>
                    </div>
                    
                    <div class="campo-pesquisa">
                        <label for="data_inicio">Data Início</label>
                        <input type="date" id="data_inicio" name="data_inicio" value="<?php echo htmlspecialchars($filtro_data_inicio ?? ''); ?>">
                    </div>
                    
                    <div class="campo-pesquisa">
                        <label for="data_fim">Data Fim</label>
                        <input type="date" id="data_fim" name="data_fim" value="<?php echo htmlspecialchars($filtro_data_fim ?? ''); ?>">
                    </div>
                </div>
            </details>
        </form>
    </section>

    <!-- Legenda -->
    <details class="legenda-container">
        <summary>Legenda de Status</summary>
        <div class="legenda">
            <div class="item-legenda">
                <span class="cor-legenda cor-inativo"></span>
                <span>Inativo</span>
            </div>
            <div class="item-legenda">
                <span class="cor-legenda cor-concluido"></span>
                <span>Concluído</span>
            </div>
            <div class="item-legenda">
                <span class="cor-legenda cor-pendente"></span>
                <span>Pendente</span>
            </div>
            <div class="item-legenda">
                <span class="cor-legenda cor-execucao"></span>
                <span>Em Execução</span>
            </div>
        </div>
    </details>

    <section class="conteudo">
        <table>
            <thead>
                <tr>
                    <th>Comum</th>
                    <th>Data Posição</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($planilhas) && count($planilhas) > 0): ?>
                    <?php foreach ($planilhas as $planilha): ?>
                    <?php
                    // Determinar as classes CSS baseadas no status e ativo
                    $classes = [];
                    if ($planilha['ativo'] == 0) {
                        $classes[] = 'inativo';
                    } else {
                        // Aplica cores apenas se estiver ativo
                        switch (strtolower($planilha['status'])) {
                            case 'concluido':
                            case 'concluído':
                                $classes[] = 'concluido';
                                break;
                            case 'pendente':
                                $classes[] = 'pendente';
                                break;
                            case 'execucao':
                            case 'em execução':
                            case 'em execucao':
                                $classes[] = 'execucao';
                                break;
                        }
                    }
                    $class_string = implode(' ', $classes);
                    
                    // Formatar data para exibição
                    $data_posicao = '';
                    if (!empty($planilha['data_posicao']) && $planilha['data_posicao'] != '0000-00-00') {
                        $data_posicao = date('d/m/Y', strtotime($planilha['data_posicao']));
                    }
                    ?>
                    <tr class="<?php echo $class_string; ?>">
                        <td class="centered"><?php echo htmlspecialchars($planilha['comum']); ?></td>
                        <td class="centered"><?php echo $data_posicao; ?></td>
                        <td class="centered"><?php echo ucfirst($planilha['status']); ?></td>
                        <td class="centered">
                            <div class="acoes-container">
                                <a href="VIEW/view-planilha.php?id=<?php echo $planilha['id']; ?>" class="btn-acao btn-visualizar">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5985E1">
                                        <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                                    </svg>
                                </a>
                                <a href="VIEW/editar-planilha.php?id=<?php echo $planilha['id']; ?>" class="btn-acao btn-editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#8C1AF6">
                                        <path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">
                            Nenhuma planilha encontrada.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <?php if (isset($total_paginas) && $total_paginas > 1): ?>
        <div class="paginacao">
            <?php if ($pagina > 1): ?>
                <a href="?pagina=<?php echo $pagina - 1; ?>&comum=<?php echo urlencode($filtro_comum ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>&ativo=<?php echo $filtro_ativo ?? '1'; ?>&data_inicio=<?php echo urlencode($filtro_data_inicio ?? ''); ?>&data_fim=<?php echo urlencode($filtro_data_fim ?? ''); ?>" class="pagina-item">
                    &laquo; Anterior
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina): ?>
                    <strong class="pagina-item ativa"><?php echo $i; ?></strong>
                <?php else: ?>
                    <a href="?pagina=<?php echo $i; ?>&comum=<?php echo urlencode($filtro_comum ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>&ativo=<?php echo $filtro_ativo ?? '1'; ?>&data_inicio=<?php echo urlencode($filtro_data_inicio ?? ''); ?>&data_fim=<?php echo urlencode($filtro_data_fim ?? ''); ?>" class="pagina-item">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagina < $total_paginas): ?>
                <a href="?pagina=<?php echo $pagina + 1; ?>&comum=<?php echo urlencode($filtro_comum ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>&ativo=<?php echo $filtro_ativo ?? '1'; ?>&data_inicio=<?php echo urlencode($filtro_data_inicio ?? ''); ?>&data_fim=<?php echo urlencode($filtro_data_fim ?? ''); ?>" class="pagina-item">
                    Próxima &raquo;
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
</body>
</html>