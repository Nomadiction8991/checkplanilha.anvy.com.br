<?php
require_once __DIR__ . '/../../../auth.php'; // Autenticacao

// Usamos o ID da comum como parametro principal (mantendo compatibilidade com "id")
$comum_id = isset($_GET['comum_id']) ? (int)$_GET['comum_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($comum_id <= 0) {
    header('Location: ../../../index.php');
    exit;
}
$_GET['comum_id'] = $comum_id;
$_GET['id'] = $comum_id;

require_once __DIR__ . '/../../../CRUD/READ/view-planilha.php';

// Configuracoes da pagina
$id_planilha = $comum_id; // compatibilidade com codigo legado
$pageTitle = htmlspecialchars($planilha['comum_descricao'] ?? 'Visualizar Planilha');
$backUrl = '../../../index.php';

// Menu diferenciado para Admin e Doador
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuPlanilha" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuPlanilha">';

// Administrador/Acessor: menu completo
if (isAdmin()) {
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="../produtos/read-produto.php?comum_id=' . $id_planilha . '">
                    <i class="bi bi-list-ul me-2"></i>Listagem de Produtos
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="../planilhas/relatorio-14-1.php?comum_id=' . $id_planilha . '">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Relat├│rio 14.1
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="../planilhas/copiar-etiquetas.php?comum_id=' . $id_planilha . '">
                    <i class="bi bi-tags me-2"></i>Copiar Etiquetas
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="../planilhas/imprimir-alteracao.php?comum_id=' . $id_planilha . '">
                    <i class="bi bi-printer me-2"></i>Imprimir Altera├º├úo
                </a>
            </li>';
} else {
    // Doador/C├┤njuge: apenas relat├│rios
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="../planilhas/relatorio-14-1.php?comum_id=' . $id_planilha . '">
                    <i class="bi bi-file-earmark-pdf me-2"></i>Relat├│rio 14.1
                </a>
            </li>';
}

$headerActions .= '
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="../../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

// Iniciar buffer para capturar o conte├║do
ob_start();
?>

<style>
/* Estilos para o bot├úo de microfone */
.mic-btn {
    /* herda totalmente o estilo do .btn (Bootstrap) */
    cursor: pointer;
    padding: 0.5rem;
    transition: all 0.3s ease;
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.mic-btn:focus,
.mic-btn:active {
    transform: none !important;
    box-shadow: none !important;
}

.mic-btn .material-icons-round {
    color: white !important;
    transition: color 0.3s ease;
}

.mic-btn.listening .material-icons-round {
    color: #dc3545 !important; /* vermelho quando gravando */
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); }
}

/* Garantir que bot├Áes do input-group n├úo se movam */
.input-group .btn {
    transform: none !important;
}

.input-group .btn:hover,
.input-group .btn:focus,
.input-group .btn:active {
    transform: none !important;
}

.mic-btn .material-icons-round {
    font-size: 20px;
    vertical-align: middle;
}

/* Estilos padr├úo para todos os dispositivos (mobile-first) */
.input-group { 
    flex-wrap: nowrap !important; 
    display: flex !important;
}

.input-group .form-control { 
    min-width: 0;
    flex: 1 1 auto !important; /* Input preenche o espa├ºo restante */
}

.input-group > .btn { 
    flex: 0 0 15% !important; /* Bot├Áes ocupam 15% cada */
    min-width: 45px !important;
    max-width: 60px !important;
    padding: 0.375rem 0.25rem !important;
    font-size: 1.1rem !important;
}

.input-group > .btn .material-icons-round,
.input-group > .btn i {
    font-size: 20px !important;
}

/* Cores das linhas baseadas no status - Paleta marcante e diferenciada */
.linha-pendente { 
    background-color: #ffffff; 
    border-left: none; 
}
.linha-checado { 
    background-color: #d4f4dd; 
    border-left: 4px solid #10b759; 
}
.linha-observacao { 
    background-color: #fff4e6; 
    border-left: 4px solid #fb8c00; 
}
.linha-imprimir { 
    background-color: #e3f2fd; 
    border-left: 4px solid #1976d2; 
}
.linha-dr { 
    background-color: #ffebee; 
    border-left: 4px solid #e53935; 
}
.linha-editado { 
    background-color: #f3e5f5; 
    border-left: 4px solid #8e24aa; 
}

/* Aviso de tipo n├úo identificado - amarelo ouro forte */
.tipo-nao-identificado {
    border-left: 4px solid #fdd835 !important;
}

/* A├º├Áes: usar padr├úo Bootstrap para bot├Áes */
.acao-container .btn { padding: 0.25rem 0.5rem; }

.edicao-pendente {
    background: #f3e5f5;
    padding: 0.5rem;
    border-radius: 0.25rem;
    margin-bottom: 0.5rem;
    border-left: 3px solid #9c27b0;
}

.observacao-produto {
    background: #fff3e0;
    padding: 0.5rem;
    border-radius: 0.25rem;
    margin-bottom: 0.5rem;
    border-left: 3px solid #ff9800;
}

.info-produto {
    font-size: 0.9rem;
    color: #555;
}

.codigo-produto {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #333;
}

.acao-container {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    margin-top: 0.5rem;
}
</style>

<!-- Link para Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">

<?php if (!empty($_GET['sucesso'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_GET['sucesso']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (!empty($erro_produtos)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    Erro ao carregar produtos: <?php echo htmlspecialchars($erro_produtos); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>
        Filtros
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
            
            <div class="mb-3">
                <label class="form-label" for="codigo">
                    <i class="bi bi-upc-scan me-1"></i>
                    C├│digo do Produto
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo" name="codigo" 
                           value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>" 
                           placeholder="Digite, fale ou escaneie o c├│digo...">
                    <button id="btnMic" class="btn btn-primary mic-btn" type="button" title="Falar c├│digo (Ctrl+M)" aria-label="Falar c├│digo" aria-pressed="false">
                        <span class="material-icons-round" aria-hidden="true">mic</span>
                    </button>
                    <button id="btnCam" class="btn btn-primary" type="button" title="Escanear c├│digo de barras" aria-label="Escanear c├│digo de barras">
                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            <div class="accordion" id="filtrosAvancados">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros">
                            <i class="bi bi-sliders me-2"></i>
                            Filtros Avan├ºados
                        </button>
                    </h2>
                    <div id="collapseFiltros" class="accordion-collapse collapse" data-bs-parent="#filtrosAvancados">
                        <div class="accordion-body">
                            <div class="mb-3">
                                <label class="form-label" for="nome">Nome</label>
                                <input type="text" class="form-control" id="nome" name="nome" 
                                       value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>" 
                                       placeholder="Pesquisar nome...">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="dependencia">Depend├¬ncia</label>
                                <select class="form-select" id="dependencia" name="dependencia">
                                    <option value="">Todas</option>
                                    <?php foreach ($dependencia_options as $dep): ?>
                                    <?php 
                                        $depId = $dep['id'] ?? '';
                                        $depDesc = $dep['descricao'] ?? $depId;
                                    ?>
                                    <option value="<?php echo htmlspecialchars($depId); ?>" 
                                        <?php echo ($filtro_dependencia ?? '') == $depId ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($depDesc); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="checado" <?php echo ($filtro_status ?? '')==='checado'?'selected':''; ?>>Checados</option>
                                    <option value="observacao" <?php echo ($filtro_status ?? '')==='observacao'?'selected':''; ?>>Com Observa├º├úo</option>
                                    <option value="etiqueta" <?php echo ($filtro_status ?? '')==='etiqueta'?'selected':''; ?>>Etiqueta para Imprimir</option>
                                    <option value="pendente" <?php echo ($filtro_status ?? '')==='pendente'?'selected':''; ?>>Pendentes</option>
                                    <option value="editado" <?php echo ($filtro_status ?? '')==='editado'?'selected':''; ?>>Editados</option>
                                </select>
                            </div>
                            
                            
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">
                <i class="bi bi-search me-2"></i>
                Filtrar
            </button>
        </form>
    </div>
    <div class="card-footer text-muted small">
        <?php echo $total_registros ?? 0; ?> registros encontrados no total
    </div>
</div>

<!-- Legenda -->
<div class="card mb-3">
    <div class="card-body p-2">
        <div class="d-flex flex-wrap gap-2 justify-content-center small">
            <span class="d-flex align-items-center gap-1">
                <span style="width: 3px; height: 16px; background-color: #10b759; display: inline-block;"></span>
                Checado
            </span>
            <span class="d-flex align-items-center gap-1">
                <span style="width: 3px; height: 16px; background-color: #fb8c00; display: inline-block;"></span>
                Observa├º├úo
            </span>
            <span class="d-flex align-items-center gap-1">
                <span style="width: 3px; height: 16px; background-color: #1976d2; display: inline-block;"></span>
                Imprimir Etiqueta
            </span>
            <span class="d-flex align-items-center gap-1">
                <span style="width: 3px; height: 16px; background-color: #e53935; display: inline-block;"></span>
                DR
            </span>
            <span class="d-flex align-items-center gap-1">
                <span style="width: 3px; height: 16px; background-color: #8e24aa; display: inline-block;"></span>
                Editado
            </span>
        </div>
        <hr class="my-2">
        <div class="d-flex flex-wrap gap-2 justify-content-center small text-muted">
            <span class="d-flex align-items-center gap-1">
                <span style="width: 3px; height: 16px; background-color: #fdd835; display: inline-block;"></span>
                Tipo de bem n├úo identificado
            </span>
        </div>
    </div>
</div>

<!-- Listagem de Produtos -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <i class="bi bi-box-seam me-2"></i>
            Produtos
        </span>
        <span class="badge bg-white text-dark"><?php echo count($produtos ?? []); ?> itens</span>
    </div>
    <div class="list-group list-group-flush">
        <?php if ($produtos): ?>
            <?php foreach ($produtos as $p): 
                // Determinar a classe com base nos status
                $classe = '';
                $tem_edicao = $p['editado'] == 1;
                
                if ($p['ativo'] == 0) {
                    $classe = 'linha-dr';
                } elseif ($p['imprimir'] == 1 && $p['checado'] == 1) {
                    $classe = 'linha-imprimir';
                } elseif ($p['checado'] == 1) {
                    $classe = 'linha-checado';
                } elseif (!empty($p['observacao'])) {
                    $classe = 'linha-observacao';
                } elseif ($tem_edicao) {
                    $classe = 'linha-editado';
                } else {
                    $classe = 'linha-pendente';
                }
                
                // Determinar quais bot├Áes mostrar
                // Se estiver em DR (ativo=0), esconder todas as a├º├Áes exceto o DR
                if ($p['ativo'] == 0) {
                    $show_check = false;
                    $show_imprimir = false;
                    $show_obs = false;
                    $show_edit = false;
                    $show_dr = true;
                } else {
                    // Regra do bot├úo de check: N├âO mostrar quando imprimir=1 ou editado=1; caso contr├írio, mostrar
                    $show_check = !($p['imprimir'] == 1 || $p['editado'] == 1);
                    $show_imprimir = ($p['checado'] == 1 && $p['editado'] == 0);
                    $show_obs = true; // Observa├º├úo dispon├¡vel quando ativo
                    $show_edit = ($p['checado'] == 0);
                    $show_dr = true; // Sempre mostrar DR
                }
            
            $tipo_invalido = (!isset($p['tipo_bem_id']) || $p['tipo_bem_id'] == 0 || empty($p['tipo_bem_id']));
            ?>
            <div class="list-group-item <?php echo $classe; ?><?php echo $tipo_invalido ? ' tipo-nao-identificado' : ''; ?>" <?php echo $tipo_invalido ? 'title="Tipo de bem n├úo identificado"' : ''; ?>>
                <!-- C├│digo -->
                <div class="codigo-produto">
                    <?php echo htmlspecialchars($p['codigo']); ?>
                </div>
                
                <!-- Edi├º├úo Pendente -->
                <?php if ($tem_edicao): ?>
                <div class="edicao-pendente">
                    <strong>Edi├º├úo:</strong><br>
                    <?php
                    // Mostrar editado_descricao_completa se existir; caso contr├írio montar uma vers├úo din├ómica
                    $desc_editada_visivel = trim($p['editado_descricao_completa'] ?? '');
                    if ($desc_editada_visivel === '') {
                        // Dados base (preferir editados)
                        $tipo_codigo_final = $p['tipo_codigo'];
                        $tipo_desc_final = $p['tipo_desc'];
                        $ben_final = ($p['editado_bem'] !== '' ? $p['editado_bem'] : $p['bem']);
                        $comp_final = ($p['editado_complemento'] !== '' ? $p['editado_complemento'] : $p['complemento']);
                        $dep_final = ($p['editado_dependencia_desc'] ?: $p['dependencia_desc']);
                        // Montagem simples (similar ├á fun├º├úo pp_montar_descricao mas sem quantidade)
                        $partes = [];
                        if ($tipo_codigo_final && $tipo_desc_final) {
                            $partes[] = strtoupper($tipo_codigo_final . ' - ' . $tipo_desc_final);
                        }
                        if ($ben_final !== '') {
                            $partes[] = strtoupper($ben_final);
                        }
                        if ($comp_final !== '') {
                            // Evitar duplica├º├úo do ben no complemento (b├ísico)
                            $comp_tmp = strtoupper($comp_final);
                            if ($ben_final !== '' && strpos($comp_tmp, strtoupper($ben_final)) === 0) {
                                $comp_tmp = trim(substr($comp_tmp, strlen($ben_final)));
                                $comp_tmp = preg_replace('/^[\s\-\/]+/','',$comp_tmp);
                            }
                            if ($comp_tmp !== '') $partes[] = $comp_tmp;
                        }
                        $desc_editada_visivel = implode(' - ', $partes);
                        if ($dep_final) {
                            $desc_editada_visivel .= ' (' . strtoupper($dep_final) . ')';
                        }
                        if ($desc_editada_visivel === '') {
                            $desc_editada_visivel = 'EDI├ç├âO SEM DESCRI├ç├âO';
                        }
                    }
                    echo htmlspecialchars($desc_editada_visivel);
                    ?><br>
                </div>
                <?php endif; ?>
                
                <!-- Observa├º├úo -->
                <?php if (!empty($p['observacao'])): ?>
                <div class="observacao-produto">
                    <strong>Observa├º├úo:</strong><br>
                    <?php echo htmlspecialchars($p['observacao']); ?><br>
                </div>
                <?php endif; ?>
                
                <!-- Informa├º├Áes -->
                <div class="info-produto">
                    <?php echo htmlspecialchars($p['descricao_completa']); ?><br>
                </div>
                
                <!-- A├º├Áes - Apenas para Administrador/Acessor -->
                <?php if (isAdmin()): ?>
                <div class="acao-container">
                    <!-- Check -->
                    <?php if ($show_check): ?>
                    <form method="POST" action="./check-produto.php" style="display: inline;">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id_produto']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="checado" value="<?php echo $p['checado'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina ?? 1; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia ?? ''); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-success btn-sm <?php echo $p['checado'] == 1 ? 'active' : ''; ?>" title="<?php echo $p['checado'] ? 'Desmarcar checado' : 'Marcar como checado'; ?>">
                            <i class="bi bi-check-circle-fill"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <!-- DEBUG: show_check ├® FALSE -->
                    <?php endif; ?>
                    
                    <!-- Etiqueta -->
                    <?php if ($show_imprimir): ?>
                    <form method="POST" action="../../../CRUD/UPDATE/etiqueta-produto.php" style="display: inline;" onsubmit="return confirmarImprimir(this, <?php echo $p['imprimir']; ?>)">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id_produto']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="imprimir" value="<?php echo $p['imprimir'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina ?? 1; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia ?? ''); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-info btn-sm <?php echo $p['imprimir'] == 1 ? 'active' : ''; ?>" title="Etiqueta">
                            <i class="bi bi-printer-fill"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Observa├º├úo -->
                    <?php if ($show_obs): ?>
                    <a href="../produtos/observacao-produto.php?id_produto=<?php echo $p['id_produto']; ?>&id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina ?? 1; ?>&nome=<?php echo urlencode($filtro_nome ?? ''); ?>&dependencia=<?php echo urlencode($filtro_dependencia ?? ''); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>"
                       class="btn btn-outline-warning btn-sm <?php echo !empty($p['observacao']) ? 'active' : ''; ?>" title="Observa├º├úo">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Editar -->
                    <?php if ($show_edit): ?>
                    <a href="../produtos/editar-produto.php?id_produto=<?php echo $p['id_produto']; ?>&id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina ?? 1; ?>&nome=<?php echo urlencode($filtro_nome ?? ''); ?>&dependencia=<?php echo urlencode($filtro_dependencia ?? ''); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>"
                       class="btn btn-outline-primary btn-sm <?php echo $tem_edicao ? 'active' : ''; ?>" title="Editar">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                    <?php endif; ?>
                    
                    <!-- DR -->
                    <?php if ($show_dr): ?>
                    <form method="POST" action="../../../CRUD/UPDATE/dr-produto.php" style="display: inline;" onsubmit="return confirmarDR(this, <?php echo $p['ativo'] == 0 ? 1 : 0; ?>)">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id_produto']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="dr" value="<?php echo $p['ativo'] == 0 ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina ?? 1; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia ?? ''); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status ?? ''); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm <?php echo $p['ativo'] == 0 ? 'active' : ''; ?>" title="DR">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; // fim do if isAdmin() ?>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="list-group-item text-center py-4">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                <span class="text-muted">Nenhum produto encontrado</span>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pagina├º├úo -->
<?php if (isset($total_paginas) && $total_paginas > 1): ?>
<nav aria-label="Navega├º├úo de p├ígina" class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <?php if ($pagina > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge(['id' => $id_planilha], $_GET, ['pagina' => $pagina - 1])); ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
        </li>
        <?php endif; ?>
        
        <?php 
        $inicio = max(1, $pagina - 2);
        $fim = min($total_paginas, $pagina + 2);
        for ($i = $inicio; $i <= $fim; $i++): 
        ?>
        <li class="page-item <?php echo $i == $pagina ? 'active' : ''; ?>">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                <?php echo $i; ?>
            </a>
        </li>
        <?php endfor; ?>
        
        <?php if ($pagina < $total_paginas): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
function confirmarDR(form, drAtual) {
    if (drAtual == 0) {
        return confirm('Tem certeza que deseja marcar este produto como DR? Esta a├º├úo ir├í limpar as observa├º├Áes e desmarcar para impress├úo.');
    } else {
        return confirm('Tem certeza que deseja desmarcar este produto do DR?');
    }
}

function confirmarImprimir(form, imprimirAtual) {
    if (imprimirAtual == 0) {
        return confirm('Tem certeza que deseja marcar este produto para impress├úo?');
    } else {
        return confirm('Tem certeza que deseja desmarcar este produto da impress├úo?');
    }
}

// ======== RECONHECIMENTO DE VOZ ========
(() => {
  const POSSIVEIS_IDS_INPUT = ["cod", "codigo", "code", "productCode", "busca", "search", "q"];
  
  function encontraInputCodigo(){
    for(const id of POSSIVEIS_IDS_INPUT){
      const el = document.getElementById(id);
      if(el) return el;
    }
    for(const name of ["cod","codigo","code","productCode","q","busca","search"]){
      const el = document.querySelector(`input[name="${name}"]`);
      if(el) return el;
    }
    const el = document.querySelector('input[placeholder*="c├│digo" i],input[placeholder*="codigo" i]');
    return el || null;
  }
  
  function encontraBotaoPesquisar(input){
    if(input && input.form){
      const b = input.form.querySelector('button[type="submit"],input[type="submit"]');
      if(b) return b;
    }
    return document.querySelector('button[type="submit"],input[type="submit"]');
  }

  let micBtn = document.getElementById('btnMic');
  if(!micBtn) return;

  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if(!SR){
        micBtn.setAttribute('aria-disabled', 'true');
        micBtn.title = 'Reconhecimento de voz n├úo suportado neste navegador';
        const iconNF = micBtn.querySelector('.material-icons-round');
        if(iconNF){ iconNF.textContent = 'mic_off'; }
        micBtn.addEventListener('click', () => {
            alert('Reconhecimento de voz n├úo ├® suportado neste navegador. Use o bot├úo de c├ómera ou digite o c├│digo.');
        });
        return;
  }

  const DIGITOS = {
    "zero":"0","um":"1","uma":"1","dois":"2","duas":"2","tr├¬s":"3","tres":"3",
    "quatro":"4","cinco":"5","seis":"6","meia":"6","sete":"7","oito":"8","nove":"9"
  };
  const SINAIS = {
    "tracinho":"-","h├¡fen":"-","hifen":"-","menos":"-",
    "barra":"/","barra invertida":"\\","contrabarra":"\\","invertida":"\\",
    "ponto":".","v├¡rgula":",","virgula":",","espa├ºo":" "
  };
  
  function extraiCodigoFalado(trans){
    let direto = trans.replace(/[^\d\-./,\\ ]+/g,'').trim();
    direto = direto.replace(/\s+/g,'');
    if(/\d/.test(direto)) return direto;

    const out = [];
    for(const raw of trans.toLowerCase().split(/\s+/)){
      const w = raw.normalize('NFD').replace(/\p{Diacritic}/gu,'');
      if(DIGITOS[w]) out.push(DIGITOS[w]);
      else if(SINAIS[w]) out.push(SINAIS[w]);
      else if(/^\d+$/.test(w)) out.push(w);
    }
    return out.join('');
  }

  async function preencherEEnviar(codigo){
    const input = encontraInputCodigo();
    if(!input){
      alert('Campo de c├│digo n├úo encontrado.');
      return;
    }
    input.focus();
    input.value = codigo;
    input.dispatchEvent(new Event('input', {bubbles:true}));
    input.dispatchEvent(new Event('change', {bubbles:true}));

    const btn = encontraBotaoPesquisar(input);
    if(btn){
      btn.click();
      return;
    }
    if(input.form){
      input.form.requestSubmit ? input.form.requestSubmit() : input.form.submit();
      return;
    }
    const ev = new KeyboardEvent('keydown', {key:'Enter', code:'Enter', bubbles:true});
    input.dispatchEvent(ev);
  }

  const rec = new SR();
  rec.lang = 'pt-BR';
  rec.continuous = false;
  rec.interimResults = false;
  rec.maxAlternatives = 3;

  function setMicIcon(listening){
    const icon = micBtn.querySelector('.material-icons-round');
    if(icon){
      icon.textContent = listening ? 'graphic_eq' : 'mic';
    }
  }

  function startListening(){
    try{
      rec.start();
      micBtn.classList.add('listening');
      micBtn.setAttribute('aria-pressed','true');
      setMicIcon(true);
    }catch(e){}
  }
  
  function stopListening(){
    try{ rec.stop(); }catch(e){}
    micBtn.classList.remove('listening');
    micBtn.setAttribute('aria-pressed','false');
    setMicIcon(false);
  }

  rec.onresult = (e) => {
    const best = e.results[0][0].transcript || '';
    const codigo = extraiCodigoFalado(best);
    stopListening();
    if(!codigo){
      alert('N├úo entendi o c├│digo. Tente soletrar: "um dois tr├¬s"ÔÇª');
      return;
    }
    preencherEEnviar(codigo);
  };
  
  rec.onerror = (e) => {
    stopListening();
    if(e.error === 'not-allowed') alert('Permita o acesso ao microfone para usar a busca por voz.');
  };
  
  rec.onend = () => micBtn.classList.remove('listening');

  micBtn.addEventListener('click', () => {
    if(micBtn.classList.contains('listening')) stopListening();
    else startListening();
  });
  
  document.addEventListener('keydown', (ev) => {
    if((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'm'){
      ev.preventDefault();
      micBtn.click();
    }
  });
})();
</script>

<!-- Modal para escanear c├│digo de barras -->
<div class="modal fade" id="barcodeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen-custom">
        <div class="modal-content bg-dark">
            <div class="modal-body p-0 position-relative">
                <div id="scanner-container" style="width:100%; height:100%; background:#000; position:relative; overflow:hidden;"></div>
                
                <!-- Bot├úo X para fechar -->
                <button type="button" class="btn-close-scanner" aria-label="Fechar scanner">
                    <i class="bi bi-x-lg"></i>
                </button>
                
                <!-- Controles de c├ómera e zoom -->
                <div class="scanner-controls">
                    <select id="cameraSelect" class="form-select form-select-sm">
                        <option value="">Carregando c├ómeras...</option>
                    </select>
                    <div class="zoom-control">
                        <i class="bi bi-zoom-out"></i>
                        <input type="range" id="zoomSlider" min="1" max="3" step="0.1" value="1" class="form-range">
                        <i class="bi bi-zoom-in"></i>
                    </div>
                </div>
                
                <!-- Overlay com moldura e dica -->
                <div class="scanner-overlay">
                    <div class="scanner-frame"></div>
                    <div class="scanner-hint">Posicione o c├│digo de barras dentro da moldura</div>
                    <div class="scanner-info" id="scannerInfo">Inicializando c├ómera...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal fullscreen customizado (95% largura x 80% altura) */
.modal-fullscreen-custom {
    width: 95vw;
    height: 80vh;
    max-width: 95vw;
    max-height: 80vh;
    margin: 10vh auto;
}

.modal-fullscreen-custom .modal-content {
    height: 100%;
    border-radius: 12px;
    overflow: hidden;
}

.modal-fullscreen-custom .modal-body {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Bot├úo X para fechar */
.btn-close-scanner {
    position: absolute;
    top: 20px;
    right: 20px;
    z-index: 1050;
    background: rgba(255, 255, 255, 0.9);
    border: none;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.btn-close-scanner:hover {
    background: rgba(255, 255, 255, 1);
    transform: scale(1.1);
}

.btn-close-scanner i {
    color: #333;
    font-size: 24px;
}

/* Overlay com moldura e dica */
.scanner-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 10;
}

.scanner-frame {
    width: 80%;
    max-width: 400px;
    height: 200px;
    border: 3px solid rgba(255, 255, 255, 0.8);
    border-radius: 12px;
    box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
    position: relative;
}

.scanner-frame::before,
.scanner-frame::after {
    content: '';
    position: absolute;
    background: #fff;
}

.scanner-frame::before {
    top: 50%;
    left: 0;
    right: 0;
    height: 2px;
    transform: translateY(-50%);
    animation: scan 2s ease-in-out infinite;
}

@keyframes scan {
    0%, 100% { opacity: 0; }
    50% { opacity: 1; }
}

.scanner-hint {
    color: white;
    background: rgba(0, 0, 0, 0.7);
    padding: 12px 24px;
    border-radius: 8px;
    margin-top: 20px;
    font-size: 14px;
    text-align: center;
    max-width: 80%;
}

.scanner-info {
    color: white;
    background: rgba(0, 0, 0, 0.8);
    padding: 8px 16px;
    border-radius: 6px;
    margin-top: 10px;
    font-size: 12px;
    text-align: center;
}

/* Controles de c├ómera e zoom */
.scanner-controls {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1050;
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 90%;
    max-width: 400px;
    pointer-events: auto;
}

.scanner-controls select {
    background: rgba(255, 255, 255, 0.95);
    border: none;
    border-radius: 8px;
    padding: 10px;
    font-size: 14px;
}

.zoom-control {
    display: flex;
    align-items: center;
    gap: 10px;
    background: rgba(255, 255, 255, 0.95);
    padding: 10px 15px;
    border-radius: 8px;
}

.zoom-control i {
    color: #333;
    font-size: 18px;
}

.zoom-control .form-range {
    flex: 1;
    margin: 0;
}

/* Container de v├¡deo do Quagga */
#scanner-container video,
#scanner-container canvas {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
}
</style>

<!-- Quagga2 para leitura de c├│digos de barras -->
<script src="https://unpkg.com/@ericblade/quagga2/dist/quagga.min.js"></script>
<script>
// Aguardar TUDO carregar (DOM + Bootstrap + Quagga)
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar mais um pouco para garantir que Bootstrap est├í pronto
    setTimeout(initBarcodeScanner, 500);
});

function initBarcodeScanner() {
    console.log('=== INICIANDO BARCODE SCANNER ===');
    
    const camBtn = document.getElementById('btnCam');
    const modalEl = document.getElementById('barcodeModal');
    
    console.log('Elementos encontrados:', {
        camBtn: !!camBtn,
        modalEl: !!modalEl,
        bootstrap: !!window.bootstrap,
        Quagga: typeof Quagga
    });
    
    if(!camBtn) {
        console.error('ERRO: Bot├úo btnCam n├úo encontrado!');
        return;
    }
    
    if(!modalEl) {
        console.error('ERRO: Modal barcodeModal n├úo encontrado!');
        return;
    }
    
    if(!window.bootstrap) {
        console.error('ERRO: Bootstrap n├úo carregado!');
        return;
    }
    
    if(typeof Quagga === 'undefined') {
        console.error('ERRO: Quagga n├úo carregado!');
        return;
    }
    
    const codigoInput = document.getElementById('codigo');
    const form = codigoInput ? (codigoInput.form || document.querySelector('form')) : document.querySelector('form');
    const scannerContainer = document.getElementById('scanner-container');
    const btnCloseScanner = document.querySelector('.btn-close-scanner');
    const cameraSelect = document.getElementById('cameraSelect');
    const zoomSlider = document.getElementById('zoomSlider');
    const scannerInfo = document.querySelector('.scanner-info');
    const bsModal = new bootstrap.Modal(modalEl, {
        backdrop: 'static',
        keyboard: false
    });
    
    let scanning = false;
    let lastCode = '';
    let currentStream = null;
    let currentTrack = null;
    let availableCameras = [];
    let selectedDeviceId = null;

    // Fun├º├úo para normalizar c├│digos (remover espa├ºos, tra├ºos, barras)
    function normalizeCode(code) {
        return code.replace(/[\s\-\/]/g, '');
    }

    // Enumerar c├ómeras dispon├¡veis
    async function enumerateCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableCameras = devices.filter(device => device.kind === 'videoinput');
            
            console.log(`­ƒô╣ ${availableCameras.length} c├ómera(s) encontrada(s)`);
            
            // Limpar e popular dropdown
            cameraSelect.innerHTML = '';
            availableCameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.textContent = camera.label || `C├ómera ${index + 1}`;
                cameraSelect.appendChild(option);
            });
            
            // Tentar selecionar c├ómera traseira como padr├úo
            const backCamera = availableCameras.find(cam => 
                cam.label.toLowerCase().includes('back') || 
                cam.label.toLowerCase().includes('traseira') ||
                cam.label.toLowerCase().includes('rear')
            );
            
            if (backCamera) {
                selectedDeviceId = backCamera.deviceId;
                cameraSelect.value = selectedDeviceId;
            } else if (availableCameras.length > 0) {
                selectedDeviceId = availableCameras[0].deviceId;
            }
            
        } catch (error) {
            console.error('ÔØî Erro ao enumerar c├ómeras:', error);
        }
    }

    // Aplicar zoom
    function applyZoom(zoomLevel) {
        if (!currentTrack) return;
        
        const capabilities = currentTrack.getCapabilities();
        if (capabilities.zoom) {
            const settings = currentTrack.getSettings();
            const maxZoom = capabilities.zoom.max;
            const minZoom = capabilities.zoom.min;
            
            // Mapear slider (1-3) para range da c├ómera
            const zoom = minZoom + ((zoomLevel - 1) / 2) * (maxZoom - minZoom);
            
            currentTrack.applyConstraints({
                advanced: [{ zoom: zoom }]
            }).then(() => {
                if (scannerInfo) {
                    scannerInfo.textContent = `Zoom: ${zoomLevel.toFixed(1)}x`;
                }
            }).catch(err => {
                console.warn('ÔÜá´©Å Zoom n├úo suportado:', err);
            });
        } else {
            console.warn('ÔÜá´©Å C├ómera n├úo suporta zoom');
            if (scannerInfo) {
                scannerInfo.textContent = 'Zoom n├úo dispon├¡vel nesta c├ómera';
            }
        }
    }

    function stopScanner(){
        console.log('­ƒøæ Parando scanner...');
        try{ 
            Quagga.stop(); 
            
            // Parar stream de v├¡deo
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
                currentStream = null;
            }
            currentTrack = null;
            
            // Limpar canvas/video elements
            if(scannerContainer) {
                while (scannerContainer.firstChild) {
                    scannerContainer.removeChild(scannerContainer.firstChild);
                }
            }
            console.log('Ô£à Scanner parado');
        }catch(e){
            console.error('ÔØî Erro ao parar scanner:', e);
        }
        scanning = false;
    }

    function startScanner(){
        if(scanning) {
            console.log('ÔÜá´©Å Scanner j├í est├í ativo');
            return;
        }
        console.log('ÔûÂ´©Å Iniciando scanner...');
        scanning = true;
        
        // Configurar constraints baseado na c├ómera selecionada
        const constraints = {
            width: { ideal: 1920 },
            height: { ideal: 1080 }
        };
        
        if (selectedDeviceId) {
            constraints.deviceId = { exact: selectedDeviceId };
        } else {
            constraints.facingMode = 'environment';
        }
        
        Quagga.init({
            inputStream: {
                type: 'LiveStream',
                target: scannerContainer,
                constraints: constraints
            },
            decoder: { 
                readers: [
                    'ean_reader',        // EAN-13 (mais comum)
                    'code_128_reader',   // CODE-128
                    'ean_8_reader',      // EAN-8
                    'upc_reader',        // UPC-A
                    'upc_e_reader'       // UPC-E
                ],
                multiple: false
            },
            locate: true,
            locator: {
                patchSize: 'large',    // Maior = mais r├ípido, menos preciso
                halfSample: true       // Processar imagem menor = mais r├ípido
            },
            frequency: 10,             // Reduzir frequ├¬ncia de localiza├º├úo = mais r├ípido
            numOfWorkers: navigator.hardwareConcurrency || 4
        }, function(err){
            if(err){
                console.error('ÔØî Erro ao iniciar scanner:', err);
                alert('N├úo foi poss├¡vel acessar a c├ómera:\n\n' + err.message + '\n\nVerifique se:\nÔ£ô Voc├¬ deu permiss├úo para usar a c├ómera\nÔ£ô O site est├í em HTTPS (ou localhost)\nÔ£ô A c├ómera n├úo est├í sendo usada por outro app');
                scanning = false;
                bsModal.hide();
                return;
            }
            console.log('Ô£à Scanner iniciado com sucesso!');
            Quagga.start();
            
            // Capturar stream para controle de zoom
            const videoElement = scannerContainer.querySelector('video');
            if (videoElement && videoElement.srcObject) {
                currentStream = videoElement.srcObject;
                const videoTracks = currentStream.getVideoTracks();
                if (videoTracks.length > 0) {
                    currentTrack = videoTracks[0];
                    
                    // Aplicar zoom inicial
                    applyZoom(parseFloat(zoomSlider.value));
                }
            }
        });

        Quagga.offDetected();
        Quagga.onDetected(function(result){
            if(!result || !result.codeResult || !result.codeResult.code) return;
            const rawCode = result.codeResult.code.trim();
            if(!rawCode || rawCode === lastCode) return;
            
            // Verificar qualidade da leitura (evitar falsos positivos)
            if(result.codeResult.decodedCodes && result.codeResult.decodedCodes.length > 0) {
                const avgError = result.codeResult.decodedCodes.reduce((sum, code) => {
                    return sum + (code.error || 0);
                }, 0) / result.codeResult.decodedCodes.length;
                
                // Se erro m├®dio muito alto, ignorar
                if(avgError > 0.12) return; // Limiar mais rigoroso para velocidade
            }
            
            // Normalizar c├│digo (remover espa├ºos, tra├ºos, barras)
            const code = normalizeCode(rawCode);
            
            console.log('­ƒôÀ C├│digo detectado:', rawCode, 'ÔåÆ normalizado:', code);
            lastCode = rawCode;
            
            // Feedback visual (borda verde)
            const frame = document.querySelector('.scanner-frame');
            if(frame) {
                frame.style.borderColor = '#28a745';
                frame.style.boxShadow = '0 0 0 9999px rgba(40, 167, 69, 0.3)';
            }
            
            // Pequeno delay para dar feedback visual
            setTimeout(() => {
                stopScanner();
                bsModal.hide();
                
                if(codigoInput){
                    codigoInput.value = code;
                    codigoInput.dispatchEvent(new Event('input',{bubbles:true}));
                    codigoInput.dispatchEvent(new Event('change',{bubbles:true}));
                }
                if(form){ 
                    form.requestSubmit ? form.requestSubmit() : form.submit(); 
                }
            }, 200); // Reduzido de 300ms para 200ms = mais r├ípido
        });
    }

    // ===== EVENTO DO BOT├âO DE C├éMERA =====
    camBtn.addEventListener('click', async function(e){
        console.log('­ƒô© Bot├úo de c├ómera CLICADO!');
        e.preventDefault();
        e.stopPropagation();
        lastCode = '';
        
        // Enumerar c├ómeras antes de abrir modal
        await enumerateCameras();
        
        console.log('­ƒÄ¼ Abrindo modal...');
        bsModal.show();
        
        // Dar tempo para o modal abrir antes de iniciar c├ómera
        setTimeout(() => {
            console.log('­ƒÄÑ Iniciando c├ómera...');
            startScanner();
        }, 400);
    });

    console.log('Ô£à Event listener da c├ómera ADICIONADO ao bot├úo');
    
    // ===== EVENTO DE MUDAN├çA DE C├éMERA =====
    if (cameraSelect) {
        cameraSelect.addEventListener('change', function(e) {
            selectedDeviceId = e.target.value;
            console.log('­ƒô╣ Mudando para c├ómera:', selectedDeviceId);
            
            // Reiniciar scanner com nova c├ómera
            if (scanning) {
                stopScanner();
                setTimeout(() => startScanner(), 300);
            }
        });
        console.log('Ô£à Event listener de sele├º├úo de c├ómera adicionado');
    }
    
    // ===== EVENTO DE CONTROLE DE ZOOM =====
    if (zoomSlider) {
        zoomSlider.addEventListener('input', function(e) {
            const zoomLevel = parseFloat(e.target.value);
            applyZoom(zoomLevel);
        });
        console.log('Ô£à Event listener de zoom adicionado');
    }

    // ===== EVENTO DO BOT├âO X =====
    if(btnCloseScanner) {
        btnCloseScanner.addEventListener('click', function(e){
            console.log('ÔØî Bot├úo X clicado');
            e.preventDefault();
            e.stopPropagation();
            stopScanner();
            bsModal.hide();
        });
        console.log('Ô£à Event listener do bot├úo X adicionado');
    }

    // ===== LIMPAR QUANDO MODAL FECHAR =====
    modalEl.addEventListener('hidden.bs.modal', function(){
        console.log('­ƒÜ¬ Modal fechado');
        stopScanner();
        // Reset visual do frame
        const frame = document.querySelector('.scanner-frame');
        if(frame) {
            frame.style.borderColor = 'rgba(255, 255, 255, 0.8)';
            frame.style.boxShadow = '0 0 0 9999px rgba(0, 0, 0, 0.5)';
        }
    });
    
    console.log('­ƒÄë === BARCODE SCANNER CONFIGURADO COM SUCESSO ===');
}
</script>

<?php
// Capturar o conte├║do
$contentHtml = ob_get_clean();

// Criar arquivo tempor├írio com o conte├║do
$tempFile = __DIR__ . '/../../../temp_view_planilha_content_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

// Renderizar o layout
include __DIR__ . '/../layouts/app-wrapper.php';

// Limpar arquivo tempor├írio
unlink($tempFile);
?>
