<?php
// Inclui o arquivo PHP que contém a lógica
require_once '../CRUD/READ/view-planilha.php';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Planilha - <?php echo htmlspecialchars($planilha['descricao']); ?></title>
    <link rel="stylesheet" href="../STYLE/view-planilha.css">
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="../index.php" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z"/></svg>
            </a>
            <h1><?php echo htmlspecialchars($planilha['descricao']); ?></h1>
        </section>
        <section class="acoes">
            <a href="menu.php?id_planilha=<?php echo $id_planilha; ?>">
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z"/></svg>
            </a>
        </section>
    </header>

    <section class="conteudo">
        <!-- Filtros -->
        <div class="filtros-container">
            <form method="GET" class="form-filtros">
                <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
                
                <div class="campo-filtro">
                    <label for="codigo">Código</label>
                    <input type="text" id="codigo" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>" placeholder="Pesquisar código">
                </div>
                
                <div class="campo-filtro">
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>" placeholder="Pesquisar nome">
                </div>
                
                <div class="campo-filtro">
                    <label for="dependencia">Dependência</label>
                    <select id="dependencia" name="dependencia">
                        <option value="">Todas</option>
                        <?php foreach ($dependencia_options as $dep): ?>
                        <option value="<?php echo htmlspecialchars($dep); ?>" <?php echo $filtro_dependencia===$dep?'selected':''; ?>>
                            <?php echo htmlspecialchars($dep); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="campo-filtro">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="">Todos</option>
                        <option value="checado" <?php echo $filtro_status==='checado'?'selected':''; ?>>Checados</option>
                        <option value="observacao" <?php echo $filtro_status==='observacao'?'selected':''; ?>>Com Observação</option>
                        <option value="etiqueta" <?php echo $filtro_status==='etiqueta'?'selected':''; ?>>Etiqueta para Imprimir</option>
                        <option value="pendente" <?php echo $filtro_status==='pendente'?'selected':''; ?>>Pendentes</option>
                        <option value="dr" <?php echo $filtro_status==='dr'?'selected':''; ?>>No DR</option>
                    </select>
                </div>
                
                <div class="botao-filtros">
                    <button type="submit" class="btn-filtrar">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                            <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                        </svg>
                        Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Legenda -->
        <div class="legenda">
            <h3>Legenda de Status</h3>
            <div class="legenda-container">
                <div class="legenda-item">
                    <div class="legenda-cor" style="background-color: #a3fab7;"></div>
                    <span>Checado</span>
                </div>
                <div class="legenda-item">
                    <div class="legenda-cor" style="background-color: #ffe8a7;"></div>
                    <span>Com Observações</span>
                </div>
                <div class="legenda-item">
                    <div class="legenda-cor" style="background-color: #a3d3fa;"></div>
                    <span>Para Imprimir</span>
                </div>
                <div class="legenda-item">
                    <div class="legenda-cor" style="background-color: #faa3a3;"></div>
                    <span>No DR</span>
                </div>
                <div class="legenda-item">
                    <div class="legenda-cor" style="background-color: #ffffaa;"></div>
                    <span>Pendente</span>
                </div>
            </div>
        </div>

        <!-- Tabela de Produtos -->
        <div class="tabela-container">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($produtos): ?>
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
                            } elseif ($p['checado'] == 0 && empty($p['observacoes']) && $p['dr'] == 0 && $p['imprimir'] == 0) {
                                $classe = 'linha-pendente';
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
                            <td class="centered"><?php echo htmlspecialchars($p['codigo']); ?></td>
                            <td>
                                <div class="acao-container">
                                    <!-- Checkbox -->
                                    <?php if ($show_check): ?>
                                    <form method="POST" action="../processar_check.php" style="display: inline;">
                                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                                        <input type="hidden" name="checado" value="<?php echo $p['checado'] ? '0' : '1'; ?>">
                                        <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
                                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
                                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">
                                        <button type="submit" class="btn-acao btn-check <?php echo $p['checado'] == 1 ? 'active' : ''; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#75FB4C"><path d="m424-312 282-282-56-56-226 226-114-114-56 56 170 170ZM200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h560q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H200Zm0-80h560v-560H200v560Zm0-560v560-560Z"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <!-- DR -->
                                    <?php if ($show_dr): ?>
                                    <form method="POST" action="../processar_dr.php" style="display: inline;" onsubmit="return confirmarDR(this, <?php echo $p['dr']; ?>)">
                                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                                        <input type="hidden" name="dr" value="<?php echo $p['dr'] ? '0' : '1'; ?>">
                                        <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
                                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
                                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">
                                        <button type="submit" class="btn-acao btn-dr <?php echo $p['dr'] == 1 ? 'active' : ''; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#EA3323"><path d="M200-640v440h560v-440H640v320l-160-80-160 80v-320H200Zm0 520q-33 0-56.5-23.5T120-200v-499q0-14 4.5-27t13.5-24l50-61q11-14 27.5-21.5T250-840h460q18 0 34.5 7.5T772-811l50 61q9 11 13.5 24t4.5 27v499q0 33-23.5 56.5T760-120H200Zm16-600h528l-34-40H250l-34 40Zm184 80v190l80-40 80 40v-190H400Zm-200 0h560-560Z"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <!-- Etiqueta -->
                                    <?php if ($show_imprimir): ?>
                                    <form method="POST" action="../processar_etiqueta.php" style="display: inline;" onsubmit="return confirmarImprimir(this, <?php echo $p['imprimir']; ?>)">
                                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                                        <input type="hidden" name="imprimir" value="<?php echo $p['imprimir'] ? '0' : '1'; ?>">
                                        <input type="hidden" name="pagina" value="<?php echo $pagina; ?>">
                                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome); ?>">
                                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia); ?>">
                                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status); ?>">
                                        <button type="submit" class="btn-acao btn-etiqueta <?php echo $p['imprimir'] == 1 ? 'active' : ''; ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#5985E1"><path d="M446-80q-15 0-30-6t-27-18L103-390q-12-12-17.5-26.5T80-446q0-15 5.5-30t17.5-27l352-353q11-11 26-17.5t31-6.5h287q33 0 56.5 23.5T879-800v287q0 16-6 30.5T856-457L503-104q-12 12-27 18t-30 6Zm0-80 353-354v-286H513L160-446l286 286Zm253-480q25 0 42.5-17.5T759-700q0-25-17.5-42.5T699-760q-25 0-42.5 17.5T639-700q0 25 17.5 42.5T699-640ZM480-480Z"/></svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <!-- Observação -->
                                    <?php if ($show_obs): ?>
                                    <a href="../processar_obs.php?codigo=<?php echo urlencode($p['codigo']); ?>&id_planilha=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"
                                       class="btn-acao btn-observacao <?php echo !empty($p['observacoes']) ? 'active' : ''; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#F19E39"><path d="M320-440h320v-80H320v80Zm0 120h320v-80H320v80Zm0 120h200v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <!-- Editar -->
                                    <?php if ($show_edit): ?>
                                    <a href="../editarproduto_planilha.php?codigo=<?php echo urlencode($p['codigo']); ?>&id_planilha=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"
                                       class="btn-acao btn-editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#8C1AF6"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h357l-80 80H200v560h560v-278l80-80v358q0 33-23.5 56.5T760-120H200Zm280-360ZM360-360v-170l367-367q12-12 27-18t30-6q16 0 30.5 6t26.5 18l56 57q11 12 17 26.5t6 29.5q0 15-5.5 29.5T897-728L530-360H360Zm481-424-56-56 56 56ZM440-440h56l232-232-28-28-29-28-231 231v57Zm260-260-29-28 29 28 28 28-28-28Z"/></svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Informações do produto -->
                                <div class="info-produto">
                                    <?php if ($tem_edicao): ?>
                                        <div class="edicao-pendente">
                                            <strong>✍ Edição pendente:</strong><br> 
                                            <?php if (!empty($p['nome_editado'])): ?>
                                                <strong>Nome:</strong> <?php echo htmlspecialchars($p['nome_editado']); ?><br>
                                            <?php endif; ?>
                                            <?php if (!empty($p['dependencia_editada'])): ?>
                                                <strong>Dep:</strong> <?php echo htmlspecialchars($p['dependencia_editada']); ?>
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
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="text-align: center; padding: 20px;">
                                <?php echo ($filtro_nome || $filtro_dependencia || $filtro_codigo || $filtro_status) ? 
                                    'Nenhum produto encontrado com os filtros aplicados.' : 
                                    'Nenhum produto cadastrado para esta planilha.'; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

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
                <a href="?id=<?php echo $id_planilha; ?>&pagina=1&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>" class="pagina-item">
                    Início
                </a>
            <?php endif; ?>

            <!-- Páginas numeradas -->
            <?php for ($i = $pagina_inicial; $i <= $pagina_final; $i++): ?>
                <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $i; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>" class="pagina-item <?php echo $i == $pagina ? 'ativa' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <!-- Última página -->
            <?php if ($pagina < $total_paginas - 1): ?>
                <a href="?id=<?php echo $id_planilha; ?>&pagina=<?php echo $total_paginas; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>" class="pagina-item">
                    Fim
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>

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