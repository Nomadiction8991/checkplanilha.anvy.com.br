<?php
// Inclui o arquivo PHP que contém a lógica
require_once '../CRUD/READ/produto.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Read Produtos</title>
    <link rel="stylesheet" href="../STYLE/read-produto.css">
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="menu-create.php?id_planilha=<?php echo $id_planilha; ?>" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px"fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z" /></svg>
            </a>
            <h1>Read Produtos</h1>
        </section>
        <section class="acoes">
            <a href="create-produto.php?id_planilha=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(true); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="M450-450H200v-60h250v-250h60v250h250v60H510v250h-60v-250Z"/></svg>
            </a>
        </section>
    </header>

<!-- Seção de Pesquisa -->
<section class="pesquisa-container">
    <form method="GET" class="form-pesquisa">
        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
        
        <div class="campo-pesquisa">
            <label for="pesquisa_id">ID</label>
            <input type="number" id="pesquisa_id" name="pesquisa_id" value="<?php echo htmlspecialchars($pesquisa_id); ?>" placeholder="Digite o ID">
        </div>
        
        <div class="campo-pesquisa">
            <label for="filtro_tipo_ben">Tipos de Bens</label> <!-- NOME ALTERADO PARA PLURAL -->
            <select id="filtro_tipo_ben" name="filtro_tipo_ben">
                <option value="">Todos</option>
                <?php foreach ($tipos_bens as $tipo): ?>
                    <option value="<?php echo $tipo['id']; ?>" <?php echo $filtro_tipo_ben == $tipo['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($tipo['codigo'] . ' - ' . $tipo['descricao']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- FILTRO BEM (nome alterado) -->
        <div class="campo-pesquisa">
            <label for="filtro_bem">Bem</label>
            <select id="filtro_bem" name="filtro_bem">
                <option value="">Todos</option>
                <?php foreach ($bem_codigos as $bem): ?>
                    <option value="<?php echo htmlspecialchars($bem['tipo_ben']); ?>" <?php echo $filtro_bem == $bem['tipo_ben'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($bem['tipo_ben']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="campo-pesquisa">
            <label for="filtro_complemento">Complemento</label>
            <input type="text" id="filtro_complemento" name="filtro_complemento" value="<?php echo htmlspecialchars($filtro_complemento); ?>" placeholder="Pesquisar no complemento">
        </div>
        
        <div class="campo-pesquisa">
            <label for="filtro_dependencia">Dependência</label>
            <select id="filtro_dependencia" name="filtro_dependencia">
                <option value="">Todas</option>
                <?php foreach ($dependencias as $dep): ?>
                    <option value="<?php echo $dep['id']; ?>" <?php echo $filtro_dependencia == $dep['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dep['descricao']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="campo-pesquisa">
            <label for="filtro_status">Status</label>
            <select id="filtro_status" name="filtro_status">
                <option value="">Todos</option>
                <option value="com_nota" <?php echo $filtro_status === 'com_nota' ? 'selected' : ''; ?>>Com Nota</option>
                <option value="com_doacao" <?php echo $filtro_status === 'com_doacao' ? 'selected' : ''; ?>>Com Doação</option>
                <option value="sem_status" <?php echo $filtro_status === 'sem_status' ? 'selected' : ''; ?>>Sem Status</option>
            </select>
        </div>
        
        <div class="botao-pesquisa">
            <button type="submit" class="btn-filtrar">
                <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                    <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                </svg>
                Filtrar
            </button>
        </div>
    </form>
</section>

    <section class="conteudo">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($produtos): ?>
                    <?php foreach ($produtos as $produto): ?>
                    <tr>
                        <td class="centered"><?php echo htmlspecialchars($produto['id']); ?></td>
                        <td class="centered">
                            <?php 
                            echo htmlspecialchars($produto['tipo_codigo'] . ' - ' . $produto['tipo_descricao']);
                            echo ' [' . htmlspecialchars($produto['tipo_ben']) . ']';
                            echo ' ' . htmlspecialchars($produto['complemento']);
                            if (!empty($produto['dependencia_descricao'])) {
                                echo ' (' . htmlspecialchars($produto['dependencia_descricao']) . ')';
                            }
                            ?>
                        </td>
                        <td class="centered">
                            <div class="status-container">
                                <?php if ($produto['possui_nota'] == 1): ?>
                                    <div class="status-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#F19E39"><path d="M320-440h320v-80H320v80Zm0 120h320v-80H320v80Zm0 120h200v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>
                                    </div>
                                <?php endif; ?>
                                <?php if ($produto['imprimir_doacao'] == 1): ?>
                                    <div class="status-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5985E1"><path d="M640-640v-120H320v120h-80v-200h480v200h-80Zm-480 80h640-640Zm560 100q17 0 28.5-11.5T760-500q0-17-11.5-28.5T720-540q-17 0-28.5 11.5T680-500q0 17 11.5 28.5T720-460Zm-80 260v-160H320v160h320Zm80 80H240v-160H80v-240q0-51 35-85.5t85-34.5h560q51 0 85.5 34.5T880-520v240H720v160Zm80-240v-160q0-17-11.5-28.5T760-560H200q-17 0-28.5 11.5T160-520v160h80v-80h480v80h80Z"/></svg>
                                    </div>
                                <?php endif; ?>
                                <?php if ($produto['possui_nota'] == 0 && $produto['imprimir_doacao'] == 0): ?>
                                    <span>-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="centered">
                            <div class="acoes-container">
                                <a href="editar-produto.php?id=<?php echo $produto['id']; ?>&id_planilha=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(true); ?>" class="btn-acao btn-editar">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#75FB4C"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/></svg>
                                </a>
                                <a href="excluir-produto.php?id=<?php echo $produto['id']; ?>&id_planilha=<?php echo $id_planilha; ?>&<?php echo gerarParametrosFiltro(true); ?>" class="btn-acao btn-excluir" onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#EA3323"><path d="M280-120q-33 0-56.5-23.5T200-200v-520h-40v-80h200v-40h240v40h200v80h-40v520q0 33-23.5 56.5T680-120H280Zm400-600H280v520h400v-520ZM360-280h80v-360h-80v360Zm160 0h80v-360h-80v360ZM280-720v520-520Z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px;">
                            <?php echo ($pesquisa_id || $pesquisa_descricao || $filtro_status) ? 
                                'Nenhum produto encontrado com os filtros aplicados.' : 
                                'Nenhum produto cadastrado para esta planilha.'; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <?php if ($total_paginas > 1): ?>
        <div class="paginacao">
            <?php
            // Calcular páginas para mostrar
            $pagina_inicial = max(1, $pagina - 1);
            $pagina_final = min($total_paginas, $pagina + 1);
            
            // Ajustar para mostrar sempre 3 páginas quando possível
            if ($pagina_final - $pagina_inicial < 2) {
                if ($pagina_inicial == 1 && $total_paginas >= 3) {
                    $pagina_final = 3;
                } elseif ($pagina_final == $total_paginas && $total_paginas >= 3) {
                    $pagina_inicial = $total_paginas - 2;
                }
            }
            ?>

            <!-- Primeira página -->
            <?php if ($pagina > 2): ?>
                <a href="?id_planilha=<?php echo $id_planilha; ?>&pagina=1&pesquisa_id=<?php echo $pesquisa_id; ?>&pesquisa_descricao=<?php echo urlencode($pesquisa_descricao); ?>&filtro_status=<?php echo $filtro_status; ?>" class="pagina-item icone">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M440-240 200-480l240-240 56 56-183 184 183 184-56 56Zm264 0L464-480l240-240 56 56-183 184 183 184-56 56Z"/>
                    </svg>
                </a>
            <?php endif; ?>

            <!-- Páginas numeradas -->
            <?php for ($i = $pagina_inicial; $i <= $pagina_final; $i++): ?>
                <a href="?id_planilha=<?php echo $id_planilha; ?>&pagina=<?php echo $i; ?>&pesquisa_id=<?php echo $pesquisa_id; ?>&pesquisa_descricao=<?php echo urlencode($pesquisa_descricao); ?>&filtro_status=<?php echo $filtro_status; ?>" class="pagina-item <?php echo $i == $pagina ? 'ativa' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <!-- Última página -->
            <?php if ($pagina < $total_paginas - 1): ?>
                <a href="?id_planilha=<?php echo $id_planilha; ?>&pagina=<?php echo $total_paginas; ?>&pesquisa_id=<?php echo $pesquisa_id; ?>&pesquisa_descricao=<?php echo urlencode($pesquisa_descricao); ?>&filtro_status=<?php echo $filtro_status; ?>" class="pagina-item icone">
                    <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="currentColor">
                        <path d="M383-480 200-664l56-56 240 240-240 240-56-56 183-184Zm264 0L464-664l56-56 240 240-240 240-56-56 183-184Z"/>
                    </svg>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
</body>
</html>