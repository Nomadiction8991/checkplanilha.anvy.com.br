<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';

// Usamos o ID da comum como parametro principal
$comum_id = isset($_GET['comum_id']) ? (int)$_GET['comum_id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);
if ($comum_id <= 0) {
    header('Location: ../../../index.php');
    exit;
}

require_once dirname(__DIR__, 2) . '/controllers/read/PlanilhaViewController.php';

// Configuracoes da pagina
$id_planilha = $comum_id; // compatibilidade com cÃƒÂ³digo legado
$pageTitle = htmlspecialchars($planilha['comum_descricao'] ?? 'Visualizar Planilha');
$backUrl = '../../../index.php';

// Bloqueio por data de importaÃƒÂ§ÃƒÂ£o (UTC-4)
if (!empty($acesso_bloqueado)) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ImportaÃƒÂ§ÃƒÂ£o necessÃƒÂ¡ria</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
        <div class="card shadow-lg" style="max-width: 480px; width: 100%;">
            <div class="card-body text-center p-4">
                <div class="mb-3 text-danger">
                    <i class="bi bi-exclamation-triangle-fill fs-1"></i>
                </div>
                <h5 class="card-title mb-3">ImportaÃƒÂ§ÃƒÂ£o desatualizada</h5>
                <p class="card-text text-muted mb-4">
                    <?php echo htmlspecialchars($mensagem_bloqueio ?: 'A planilha precisa ser importada novamente para continuar.'); ?>
                </p>
                <a href="../planilhas/planilha_importar.php" class="btn btn-primary w-100">
                    Importar planilha atualizada
                </a>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

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
                <a class="dropdown-item" href="../produtos/produtos_listar.php?comum_id=' . $comum_id . '">
                    <i class="bi bi-list-ul me-2"></i>Listagem de Produtos
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="../planilhas/relatorio141_view.php?id=' . $id_planilha . '&comum_id=' . $comum_id . '">
                    <i class="bi bi-file-earmark-pdf me-2"></i>RelatÃ¢â€Å“Ã¢â€â€šrio 14.1
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="../planilhas/produto_copiar_etiquetas.php?id=' . $id_planilha . '&comum_id=' . $comum_id . '">
                    <i class="bi bi-tags me-2"></i>Copiar Etiquetas
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="../planilhas/relatorio_imprimir_alteracao.php?id=' . $id_planilha . '&comum_id=' . $comum_id . '">
                    <i class="bi bi-printer me-2"></i>Imprimir AlteraÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo
                </a>
            </li>';
} else {
    // Doador/CÃ¢â€Å“Ã¢â€Â¤njuge: apenas relatÃ¢â€Å“Ã¢â€â€šrios
    $headerActions .= '
            <li>
                <a class="dropdown-item" href="../planilhas/relatorio141_view.php?id=' . $id_planilha . '&comum_id=' . $comum_id . '">
                    <i class="bi bi-file-earmark-pdf me-2"></i>RelatÃ¢â€Å“Ã¢â€â€šrio 14.1
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

// Iniciar buffer para capturar o conteÃ¢â€Å“Ã¢â€¢â€˜do
ob_start();
?>

<style>
/* Estilos para o botÃ¢â€Å“ÃƒÂºo de microfone */
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

/* Garantir que botÃ¢â€Å“ÃƒÂes do input-group nÃ¢â€Å“ÃƒÂºo se movam */
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

/* Estilos padrÃ¢â€Å“ÃƒÂºo para todos os dispositivos (mobile-first) */
.input-group { 
    flex-wrap: nowrap !important; 
    display: flex !important;
}

.input-group .form-control { 
    min-width: 0;
    flex: 1 1 auto !important; /* Input preenche o espaÃ¢â€Å“Ã‚Âºo restante */
}

.input-group > .btn { 
    flex: 0 0 15% !important; /* BotÃ¢â€Å“ÃƒÂes ocupam 15% cada */
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

/* Aviso de tipo nÃ¢â€Å“ÃƒÂºo identificado - amarelo ouro forte */
.tipo-nao-identificado {
    border-left: 4px solid #fdd835 !important;
}

/* AÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes: usar padrÃ¢â€Å“ÃƒÂºo Bootstrap para botÃ¢â€Å“ÃƒÂes */
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
                    CÃ¢â€Å“Ã¢â€â€šdigo do Produto
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo" name="codigo" 
                           value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>" 
                           placeholder="Digite, fale ou escaneie o cÃ¢â€Å“Ã¢â€â€šdigo...">
                    <button id="btnMic" class="btn btn-primary mic-btn" type="button" title="Falar cÃ¢â€Å“Ã¢â€â€šdigo (Ctrl+M)" aria-label="Falar cÃ¢â€Å“Ã¢â€â€šdigo" aria-pressed="false">
                        <span class="material-icons-round" aria-hidden="true">mic</span>
                    </button>
                    <button id="btnCam" class="btn btn-primary" type="button" title="Escanear cÃ¢â€Å“Ã¢â€â€šdigo de barras" aria-label="Escanear cÃ¢â€Å“Ã¢â€â€šdigo de barras">
                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            <div class="accordion" id="filtrosAvancados">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros">
                            <i class="bi bi-sliders me-2"></i>
                            Filtros AvanÃ¢â€Å“Ã‚Âºados
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
                                <label class="form-label" for="dependencia">DependÃ¢â€Å“Ã‚Â¬ncia</label>
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
                                    <option value="observacao" <?php echo ($filtro_status ?? '')==='observacao'?'selected':''; ?>>Com ObservaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo</option>
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
                ObservaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo
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
                Tipo de bem nÃ¢â€Å“ÃƒÂºo identificado
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
                
                // Determinar quais botÃ¢â€Å“ÃƒÂes mostrar
                // Se estiver em DR (ativo=0), esconder todas as aÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes exceto o DR
                if ($p['ativo'] == 0) {
                    $show_check = false;
                    $show_imprimir = false;
                    $show_obs = false;
                    $show_edit = false;
                    $show_dr = true;
                } else {
                    // Regra do botÃ¢â€Å“ÃƒÂºo de check: NÃ¢â€Å“ÃƒÂ¢O mostrar quando imprimir=1 ou editado=1; caso contrÃ¢â€Å“ÃƒÂ­rio, mostrar
                    $show_check = !($p['imprimir'] == 1 || $p['editado'] == 1);
                    $show_imprimir = ($p['checado'] == 1 && $p['editado'] == 0);
                    $show_obs = true; // ObservaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo disponÃ¢â€Å“Ã‚Â¡vel quando ativo
                    $show_edit = ($p['checado'] == 0);
                    $show_dr = true; // Sempre mostrar DR
                }
            
            $tipo_invalido = (!isset($p['tipo_bem_id']) || $p['tipo_bem_id'] == 0 || empty($p['tipo_bem_id']));
            ?>
            <div 
                class="list-group-item <?php echo $classe; ?><?php echo $tipo_invalido ? ' tipo-nao-identificado' : ''; ?>" 
                data-produto-id="<?php echo $p['id_produto']; ?>"
                data-ativo="<?php echo (int) $p['ativo']; ?>"
                data-checado="<?php echo (int) $p['checado']; ?>"
                data-imprimir="<?php echo (int) $p['imprimir']; ?>"
                data-observacao="<?php echo htmlspecialchars($p['observacao'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                data-editado="<?php echo (int) $p['editado']; ?>"
                <?php echo $tipo_invalido ? 'title="Tipo de bem nÃ¢â€Å“ÃƒÂºo identificado"' : ''; ?>
            >
                <!-- CÃ¢â€Å“Ã¢â€â€šdigo -->
                <div class="codigo-produto">
                    <?php echo htmlspecialchars($p['codigo']); ?>
                </div>
                
                <!-- EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo Pendente -->
                <?php if ($tem_edicao): ?>
                <div class="edicao-pendente">
                    <strong>EdiÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo:</strong><br>
                    <?php
                    // Mostrar editado_descricao_completa se existir; caso contrÃ¢â€Å“ÃƒÂ­rio montar uma versÃ¢â€Å“ÃƒÂºo dinÃ¢â€Å“ÃƒÂ³mica
                    $desc_editada_visivel = trim($p['editado_descricao_completa'] ?? '');
                    if ($desc_editada_visivel === '') {
                        // Dados base (preferir editados)
                        $tipo_codigo_final = $p['tipo_codigo'];
                        $tipo_desc_final = $p['tipo_desc'];
                        $ben_final = ($p['editado_bem'] !== '' ? $p['editado_bem'] : $p['bem']);
                        $comp_final = ($p['editado_complemento'] !== '' ? $p['editado_complemento'] : $p['complemento']);
                        $dep_final = ($p['editado_dependencia_desc'] ?: $p['dependencia_desc']);
                        // Montagem simples (similar Ã¢â€Å“ÃƒÂ¡ funÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo pp_montar_descricao mas sem quantidade)
                        $partes = [];
                        if ($tipo_codigo_final && $tipo_desc_final) {
                            $partes[] = strtoupper($tipo_codigo_final . ' - ' . $tipo_desc_final);
                        }
                        if ($ben_final !== '') {
                            $partes[] = strtoupper($ben_final);
                        }
                        if ($comp_final !== '') {
                            // Evitar duplicaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo do ben no complemento (bÃ¢â€Å“ÃƒÂ­sico)
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
                            $desc_editada_visivel = 'EDIÃ¢â€Å“ÃƒÂ§Ã¢â€Å“ÃƒÂ¢O SEM DESCRIÃ¢â€Å“ÃƒÂ§Ã¢â€Å“ÃƒÂ¢O';
                        }
                    }
                    echo htmlspecialchars($desc_editada_visivel);
                    ?><br>
                </div>
                <?php endif; ?>
                
                <!-- ObservaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo -->
                <?php if (!empty($p['observacao'])): ?>
                <div class="observacao-produto">
                    <strong>ObservaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo:</strong><br>
                    <?php echo htmlspecialchars($p['observacao']); ?><br>
                </div>
                <?php endif; ?>
                
                <!-- InformaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes -->
                <div class="info-produto">
                    <?php echo htmlspecialchars($p['descricao_completa']); ?><br>
                </div>
                
                <!-- AÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes - Apenas para Administrador/Acessor -->
                <?php if (isAdmin()): ?>
                <div class="acao-container">
                    <!-- Check -->
                    <form method="POST" action="../../../app/controllers/update/ProdutoCheckController.php" style="display: <?php echo $show_check ? 'inline' : 'none'; ?>;" class="produto-action-form action-check" data-action="check" data-produto-id="<?php echo $p['id_produto']; ?>">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id_produto']; ?>">
                        <input type="hidden" name="comum_id" value="<?php echo $comum_id; ?>">
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
                    
                    <!-- Etiqueta -->
                    <form method="POST" action="../../../app/controllers/update/ProdutoEtiquetaController.php" style="display: <?php echo $show_imprimir ? 'inline' : 'none'; ?>;" class="produto-action-form action-imprimir" data-action="imprimir" data-produto-id="<?php echo $p['id_produto']; ?>" data-confirm="<?php echo $p['imprimir'] ? 'Deseja desmarcar este produto para etiqueta?' : 'Deseja marcar este produto para etiqueta?'; ?>">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id_produto']; ?>">
                        <input type="hidden" name="comum_id" value="<?php echo $comum_id; ?>">
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
                    
                    <!-- Observa??o -->
                    <a href="../produtos/produto_observacao.php?id_produto=<?php echo $p['id_produto']; ?>&comum_id=<?php echo $comum_id; ?>&pagina=<?php echo $pagina ?? 1; ?>&nome=<?php echo urlencode($filtro_nome ?? ''); ?>&dependencia=<?php echo urlencode($filtro_dependencia ?? ''); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>"
                       class="btn btn-outline-warning btn-sm action-observacao <?php echo !empty($p['observacao']) ? 'active' : ''; ?>" style="display: <?php echo $show_obs ? 'inline-block' : 'none'; ?>;" title="Observa??o">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </a>
                    
                    <!-- Editar -->
                    <a href="../produtos/produto_editar.php?id_produto=<?php echo $p['id_produto']; ?>&comum_id=<?php echo $comum_id; ?>&pagina=<?php echo $pagina ?? 1; ?>&nome=<?php echo urlencode($filtro_nome ?? ''); ?>&dependencia=<?php echo urlencode($filtro_dependencia ?? ''); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>"
                       class="btn btn-outline-primary btn-sm action-editar <?php echo $tem_edicao ? 'active' : ''; ?>" style="display: <?php echo $show_edit ? 'inline-block' : 'none'; ?>;" title="Editar">
                        <i class="bi bi-pencil-fill"></i>
                    </a>
                    
                    <!-- DR -->
                    <?php if ($show_dr): ?>
                    <?php $drConfirm = $p['ativo'] == 0 ? 'Tem certeza que deseja desmarcar este produto do DR?' : 'Tem certeza que deseja marcar este produto como DR? Esta aÃƒÂ§ÃƒÂ£o irÃƒÂ¡ limpar observaÃƒÂ§ÃƒÂµes e desmarcar para impressÃƒÂ£o.'; ?>
                    <form method="POST" action="../../../app/controllers/update/ProdutoDrUpdateController.php" style="display: inline;" class="produto-action-form action-dr" data-action="dr" data-produto-id="<?php echo $p['id_produto']; ?>" data-confirm="<?php echo htmlspecialchars($drConfirm, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id_produto']; ?>">
                        <input type="hidden" name="comum_id" value="<?php echo $comum_id; ?>">
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

<!-- PaginaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo -->
<?php if (isset($total_paginas) && $total_paginas > 1): ?>
<nav aria-label="NavegaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo de pÃ¢â€Å“ÃƒÂ­gina" class="mt-3">
    <ul class="pagination pagination-sm justify-content-center mb-0">
        <?php if ($pagina > 1): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $_GET, ['pagina' => $pagina - 1])); ?>">
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
            <a class="page-link" href="?<?php echo http_build_query(array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $_GET, ['pagina' => $i])); ?>">
                <?php echo $i; ?>
            </a>
        </li>
        <?php endfor; ?>
        
        <?php if ($pagina < $total_paginas): ?>
        <li class="page-item">
            <a class="page-link" href="?<?php echo http_build_query(array_merge(['id' => $comum_id, 'comum_id' => $comum_id], $_GET, ['pagina' => $pagina + 1])); ?>">
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
        return confirm('Tem certeza que deseja marcar este produto como DR? Esta aÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo irÃ¢â€Å“ÃƒÂ­ limpar as observaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂes e desmarcar para impressÃ¢â€Å“ÃƒÂºo.');
    } else {
        return confirm('Tem certeza que deseja desmarcar este produto do DR?');
    }
}

function confirmarImprimir(form, imprimirAtual) {
    if (imprimirAtual == 0) {
        return confirm('Tem certeza que deseja marcar este produto para impressÃ¢â€Å“ÃƒÂºo?');
    } else {
        return confirm('Tem certeza que deseja desmarcar este produto da impressÃ¢â€Å“ÃƒÂºo?');
    }
}

// ======== AÃ¢â€Å“ÃƒÂ§Ã¢â€Å“ÃƒÂºES AJAX (check/etiqueta/DR) ========
document.addEventListener('DOMContentLoaded', () => {
    const alertHost = document.createElement('div');
    alertHost.id = 'ajaxAlerts';
    alertHost.className = 'position-fixed top-0 start-50 translate-middle-x p-3';
    alertHost.style.zIndex = '1100';
    document.body.appendChild(alertHost);

    const showAlert = (type, message) => {
        const wrapper = document.createElement('div');
        wrapper.className = `alert alert-${type} alert-dismissible fade show shadow-sm`;
        wrapper.role = 'alert';
        wrapper.innerHTML = `
            <div class="d-flex align-items-center gap-2">
                <i class="bi ${type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}"></i>
                <span>${message}</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        `;
        alertHost.appendChild(wrapper);
        setTimeout(() => {
            wrapper.classList.remove('show');
            wrapper.addEventListener('transitionend', () => wrapper.remove(), { once: true });
        }, 3000);
    };

    const linhaClasses = ['linha-dr','linha-imprimir','linha-checado','linha-observacao','linha-editado','linha-pendente'];
    const computeRowClass = (state) => {
        if (state.ativo === 0) return 'linha-dr';
        if (state.imprimir === 1 && state.checado === 1) return 'linha-imprimir';
        if (state.checado === 1) return 'linha-checado';
        if ((state.observacao || '').trim() !== '') return 'linha-observacao';
        if (state.editado === 1) return 'linha-editado';
        return 'linha-pendente';
    };

    const getRowState = (row) => ({
        ativo: Number(row.dataset.ativo || 0),
        checado: Number(row.dataset.checado || 0),
        imprimir: Number(row.dataset.imprimir || 0),
        observacao: row.dataset.observacao || '',
        editado: Number(row.dataset.editado || 0)
    });

    const updateActionButtons = (row, state) => {
        const showCheck = !(state.ativo === 0) && !(state.imprimir === 1 || state.editado === 1);
        const showImprimir = state.ativo === 1 && state.checado === 1 && state.editado === 0;
        const showObs = state.ativo === 1;
        const showEdit = state.ativo === 1 && state.checado === 0;

        row.querySelectorAll('.action-check').forEach(el => el.style.display = showCheck ? 'inline-block' : 'none');
        row.querySelectorAll('.action-imprimir').forEach(el => el.style.display = showImprimir ? 'inline-block' : 'none');
        row.querySelectorAll('.action-dr').forEach(el => el.style.display = 'inline-block');
        row.querySelectorAll('.btn-outline-warning').forEach(el => el.style.display = showObs ? 'inline-block' : 'none');
        row.querySelectorAll('.btn-outline-primary').forEach(el => el.style.display = showEdit ? 'inline-block' : 'none');
    };

    const applyState = (row, updates = {}) => {
        const state = { ...getRowState(row), ...updates };
        row.dataset.ativo = state.ativo;
        row.dataset.checado = state.checado;
        row.dataset.imprimir = state.imprimir;
        row.dataset.observacao = state.observacao ?? '';
        row.dataset.editado = state.editado ?? row.dataset.editado;

        linhaClasses.forEach(c => row.classList.remove(c));
        row.classList.add(computeRowClass(state));
        updateActionButtons(row, state);
    };

    document.querySelectorAll('.list-group-item[data-produto-id]').forEach(row => {
        updateActionButtons(row, getRowState(row));
    });

    document.querySelectorAll('.produto-action-form').forEach(form => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const action = form.dataset.action;
            const produtoId = form.dataset.produtoId;
            const confirmMsg = form.dataset.confirm;
            if (confirmMsg && !confirm(confirmMsg)) {
                return;
            }

            const formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                let data = {};
                try {
                    data = await response.json();
                } catch (e) {
                    // resposta nÃƒÂ£o era JSON
                }
                if (!response.ok || data.success === false) {
                    throw new Error(data.message || 'NÃƒÂ£o foi possÃƒÂ­vel atualizar.');
                }
                return data;
            })
            .then(data => {

                const row = document.querySelector(`.list-group-item[data-produto-id="${produtoId}"]`);
                const stateUpdates = {};

                if (action === 'check') {
                    const newVal = Number(formData.get('checado') || 0);
                    stateUpdates.checado = newVal;
                    const input = form.querySelector('input[name=\"checado\"]');
                    if (input) { input.value = newVal ? '0' : '1'; }
                    const btn = form.querySelector('button');
                    if (btn) { btn.classList.toggle('active', newVal === 1); }
                } else if (action === 'imprimir') {
                    const newVal = Number(formData.get('imprimir') || 0);
                    stateUpdates.imprimir = newVal;
                    const input = form.querySelector('input[name=\"imprimir\"]');
                    if (input) { input.value = newVal ? '0' : '1'; }
                    const btn = form.querySelector('button');
                    if (btn) { btn.classList.toggle('active', newVal === 1); }
                } else if (action === 'dr') {
                    const newVal = Number(formData.get('dr') || 0);
                    const ativo = newVal === 1 ? 0 : 1;
                    stateUpdates.ativo = ativo;
                    stateUpdates.checado = ativo === 0 ? 0 : getRowState(row).checado;
                    stateUpdates.imprimir = ativo === 0 ? 0 : getRowState(row).imprimir;
                    stateUpdates.observacao = ativo === 0 ? '' : getRowState(row).observacao;
                    const input = form.querySelector('input[name=\"dr\"]');
                    if (input) { input.value = newVal === 1 ? '0' : '1'; }
                    const btn = form.querySelector('button');
                    if (btn) { btn.classList.toggle('active', ativo === 0); }
                }

                if (row) {
                    applyState(row, stateUpdates);
                }

                showAlert('success', data.message || 'Status atualizado.');
            })
            .catch(err => {
                showAlert('danger', err.message || 'Erro ao processar aÃƒÂ§ÃƒÂ£o.');
            });
        });
    });
});

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
    const el = document.querySelector('input[placeholder*="cÃ¢â€Å“Ã¢â€â€šdigo" i],input[placeholder*="codigo" i]');
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
        micBtn.title = 'Reconhecimento de voz nÃ¢â€Å“ÃƒÂºo suportado neste navegador';
        const iconNF = micBtn.querySelector('.material-icons-round');
        if(iconNF){ iconNF.textContent = 'mic_off'; }
        micBtn.addEventListener('click', () => {
            alert('Reconhecimento de voz nÃ¢â€Å“ÃƒÂºo Ã¢â€Å“Ã‚Â® suportado neste navegador. Use o botÃ¢â€Å“ÃƒÂºo de cÃ¢â€Å“ÃƒÂ³mera ou digite o cÃ¢â€Å“Ã¢â€â€šdigo.');
        });
        return;
  }

  const DIGITOS = {
    "zero":"0","um":"1","uma":"1","dois":"2","duas":"2","trÃ¢â€Å“Ã‚Â¬s":"3","tres":"3",
    "quatro":"4","cinco":"5","seis":"6","meia":"6","sete":"7","oito":"8","nove":"9"
  };
  const SINAIS = {
    "tracinho":"-","hÃ¢â€Å“Ã‚Â¡fen":"-","hifen":"-","menos":"-",
    "barra":"/","barra invertida":"\\","contrabarra":"\\","invertida":"\\",
    "ponto":".","vÃ¢â€Å“Ã‚Â¡rgula":",","virgula":",","espaÃ¢â€Å“Ã‚Âºo":" "
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
      alert('Campo de cÃ¢â€Å“Ã¢â€â€šdigo nÃ¢â€Å“ÃƒÂºo encontrado.');
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
      alert('NÃ¢â€Å“ÃƒÂºo entendi o cÃ¢â€Å“Ã¢â€â€šdigo. Tente soletrar: "um dois trÃ¢â€Å“Ã‚Â¬s"Ãƒâ€Ãƒâ€¡Ã‚Âª');
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

<!-- Modal para escanear cÃ¢â€Å“Ã¢â€â€šdigo de barras -->
<div class="modal fade" id="barcodeModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-fullscreen-custom">
        <div class="modal-content bg-dark">
            <div class="modal-body p-0 position-relative">
                <div id="scanner-container" style="width:100%; height:100%; background:#000; position:relative; overflow:hidden;"></div>
                
                <!-- BotÃ¢â€Å“ÃƒÂºo X para fechar -->
                <button type="button" class="btn-close-scanner" aria-label="Fechar scanner">
                    <i class="bi bi-x-lg"></i>
                </button>
                
                <!-- Controles de cÃ¢â€Å“ÃƒÂ³mera e zoom -->
                <div class="scanner-controls">
                    <select id="cameraSelect" class="form-select form-select-sm">
                        <option value="">Carregando cÃ¢â€Å“ÃƒÂ³meras...</option>
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
                    <div class="scanner-hint">Posicione o cÃ¢â€Å“Ã¢â€â€šdigo de barras dentro da moldura</div>
                    <div class="scanner-info" id="scannerInfo">Inicializando cÃ¢â€Å“ÃƒÂ³mera...</div>
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

/* BotÃ¢â€Å“ÃƒÂºo X para fechar */
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

/* Controles de cÃ¢â€Å“ÃƒÂ³mera e zoom */
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

/* Container de vÃ¢â€Å“Ã‚Â¡deo do Quagga */
#scanner-container video,
#scanner-container canvas {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
}
</style>

<!-- Quagga2 para leitura de cÃ¢â€Å“Ã¢â€â€šdigos de barras -->
<script src="https://unpkg.com/@ericblade/quagga2/dist/quagga.min.js"></script>
<script>
// Aguardar TUDO carregar (DOM + Bootstrap + Quagga)
document.addEventListener('DOMContentLoaded', function() {
    // Aguardar mais um pouco para garantir que Bootstrap estÃ¢â€Å“ÃƒÂ­ pronto
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
        console.error('ERRO: BotÃ¢â€Å“ÃƒÂºo btnCam nÃ¢â€Å“ÃƒÂºo encontrado!');
        return;
    }
    
    if(!modalEl) {
        console.error('ERRO: Modal barcodeModal nÃ¢â€Å“ÃƒÂºo encontrado!');
        return;
    }
    
    if(!window.bootstrap) {
        console.error('ERRO: Bootstrap nÃ¢â€Å“ÃƒÂºo carregado!');
        return;
    }
    
    if(typeof Quagga === 'undefined') {
        console.error('ERRO: Quagga nÃ¢â€Å“ÃƒÂºo carregado!');
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

    // FunÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo para normalizar cÃ¢â€Å“Ã¢â€â€šdigos (remover espaÃ¢â€Å“Ã‚Âºos, traÃ¢â€Å“Ã‚Âºos, barras)
    function normalizeCode(code) {
        return code.replace(/[\s\-\/]/g, '');
    }

    // Enumerar cÃ¢â€Å“ÃƒÂ³meras disponÃ¢â€Å“Ã‚Â¡veis
    async function enumerateCameras() {
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            availableCameras = devices.filter(device => device.kind === 'videoinput');
            
            console.log(`Ã‚Â­Ã†â€™ÃƒÂ´Ã¢â€¢Â£ ${availableCameras.length} cÃ¢â€Å“ÃƒÂ³mera(s) encontrada(s)`);
            
            // Limpar e popular dropdown
            cameraSelect.innerHTML = '';
            availableCameras.forEach((camera, index) => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.textContent = camera.label || `CÃ¢â€Å“ÃƒÂ³mera ${index + 1}`;
                cameraSelect.appendChild(option);
            });
            
            // Tentar selecionar cÃ¢â€Å“ÃƒÂ³mera traseira como padrÃ¢â€Å“ÃƒÂºo
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
            console.error('Ãƒâ€ÃƒËœÃƒÂ® Erro ao enumerar cÃ¢â€Å“ÃƒÂ³meras:', error);
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
            
            // Mapear slider (1-3) para range da cÃ¢â€Å“ÃƒÂ³mera
            const zoom = minZoom + ((zoomLevel - 1) / 2) * (maxZoom - minZoom);
            
            currentTrack.applyConstraints({
                advanced: [{ zoom: zoom }]
            }).then(() => {
                if (scannerInfo) {
                    scannerInfo.textContent = `Zoom: ${zoomLevel.toFixed(1)}x`;
                }
            }).catch(err => {
                console.warn('Ãƒâ€ÃƒÅ“ÃƒÂ¡Ã‚Â´Ã‚Â©Ãƒâ€¦ Zoom nÃ¢â€Å“ÃƒÂºo suportado:', err);
            });
        } else {
            console.warn('Ãƒâ€ÃƒÅ“ÃƒÂ¡Ã‚Â´Ã‚Â©Ãƒâ€¦ CÃ¢â€Å“ÃƒÂ³mera nÃ¢â€Å“ÃƒÂºo suporta zoom');
            if (scannerInfo) {
                scannerInfo.textContent = 'Zoom nÃ¢â€Å“ÃƒÂºo disponÃ¢â€Å“Ã‚Â¡vel nesta cÃ¢â€Å“ÃƒÂ³mera';
            }
        }
    }

    function stopScanner(){
        console.log('Ã‚Â­Ã†â€™ÃƒÂ¸ÃƒÂ¦ Parando scanner...');
        try{ 
            Quagga.stop(); 
            
            // Parar stream de vÃ¢â€Å“Ã‚Â¡deo
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
            console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Scanner parado');
        }catch(e){
            console.error('Ãƒâ€ÃƒËœÃƒÂ® Erro ao parar scanner:', e);
        }
        scanning = false;
    }

    function startScanner(){
        if(scanning) {
            console.log('Ãƒâ€ÃƒÅ“ÃƒÂ¡Ã‚Â´Ã‚Â©Ãƒâ€¦ Scanner jÃ¢â€Å“ÃƒÂ­ estÃ¢â€Å“ÃƒÂ­ ativo');
            return;
        }
        console.log('Ãƒâ€ÃƒÂ»Ãƒâ€šÃ‚Â´Ã‚Â©Ãƒâ€¦ Iniciando scanner...');
        scanning = true;
        
        // Configurar constraints baseado na cÃ¢â€Å“ÃƒÂ³mera selecionada
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
                patchSize: 'large',    // Maior = mais rÃ¢â€Å“ÃƒÂ­pido, menos preciso
                halfSample: true       // Processar imagem menor = mais rÃ¢â€Å“ÃƒÂ­pido
            },
            frequency: 10,             // Reduzir frequÃ¢â€Å“Ã‚Â¬ncia de localizaÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo = mais rÃ¢â€Å“ÃƒÂ­pido
            numOfWorkers: navigator.hardwareConcurrency || 4
        }, function(err){
            if(err){
                console.error('Ãƒâ€ÃƒËœÃƒÂ® Erro ao iniciar scanner:', err);
                alert('NÃ¢â€Å“ÃƒÂºo foi possÃ¢â€Å“Ã‚Â¡vel acessar a cÃ¢â€Å“ÃƒÂ³mera:\n\n' + err.message + '\n\nVerifique se:\nÃƒâ€Ã‚Â£ÃƒÂ´ VocÃ¢â€Å“Ã‚Â¬ deu permissÃ¢â€Å“ÃƒÂºo para usar a cÃ¢â€Å“ÃƒÂ³mera\nÃƒâ€Ã‚Â£ÃƒÂ´ O site estÃ¢â€Å“ÃƒÂ­ em HTTPS (ou localhost)\nÃƒâ€Ã‚Â£ÃƒÂ´ A cÃ¢â€Å“ÃƒÂ³mera nÃ¢â€Å“ÃƒÂºo estÃ¢â€Å“ÃƒÂ­ sendo usada por outro app');
                scanning = false;
                bsModal.hide();
                return;
            }
            console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Scanner iniciado com sucesso!');
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
                
                // Se erro mÃ¢â€Å“Ã‚Â®dio muito alto, ignorar
                if(avgError > 0.12) return; // Limiar mais rigoroso para velocidade
            }
            
            // Normalizar cÃ¢â€Å“Ã¢â€â€šdigo (remover espaÃ¢â€Å“Ã‚Âºos, traÃ¢â€Å“Ã‚Âºos, barras)
            const code = normalizeCode(rawCode);
            
            console.log('Ã‚Â­Ã†â€™ÃƒÂ´Ãƒâ‚¬ CÃ¢â€Å“Ã¢â€â€šdigo detectado:', rawCode, 'Ãƒâ€ÃƒÂ¥Ãƒâ€  normalizado:', code);
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
            }, 200); // Reduzido de 300ms para 200ms = mais rÃ¢â€Å“ÃƒÂ­pido
        });
    }

    // ===== EVENTO DO BOTÃ¢â€Å“ÃƒÂ¢O DE CÃ¢â€Å“ÃƒÂ©MERA =====
    camBtn.addEventListener('click', async function(e){
        console.log('Ã‚Â­Ã†â€™ÃƒÂ´Ã‚Â© BotÃ¢â€Å“ÃƒÂºo de cÃ¢â€Å“ÃƒÂ³mera CLICADO!');
        e.preventDefault();
        e.stopPropagation();
        lastCode = '';
        
        // Enumerar cÃ¢â€Å“ÃƒÂ³meras antes de abrir modal
        await enumerateCameras();
        
        console.log('Ã‚Â­Ã†â€™Ãƒâ€žÃ‚Â¼ Abrindo modal...');
        bsModal.show();
        
        // Dar tempo para o modal abrir antes de iniciar cÃ¢â€Å“ÃƒÂ³mera
        setTimeout(() => {
            console.log('Ã‚Â­Ã†â€™Ãƒâ€žÃƒâ€˜ Iniciando cÃ¢â€Å“ÃƒÂ³mera...');
            startScanner();
        }, 400);
    });

    console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Event listener da cÃ¢â€Å“ÃƒÂ³mera ADICIONADO ao botÃ¢â€Å“ÃƒÂºo');
    
    // ===== EVENTO DE MUDANÃ¢â€Å“ÃƒÂ§A DE CÃ¢â€Å“ÃƒÂ©MERA =====
    if (cameraSelect) {
        cameraSelect.addEventListener('change', function(e) {
            selectedDeviceId = e.target.value;
            console.log('Ã‚Â­Ã†â€™ÃƒÂ´Ã¢â€¢Â£ Mudando para cÃ¢â€Å“ÃƒÂ³mera:', selectedDeviceId);
            
            // Reiniciar scanner com nova cÃ¢â€Å“ÃƒÂ³mera
            if (scanning) {
                stopScanner();
                setTimeout(() => startScanner(), 300);
            }
        });
        console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Event listener de seleÃ¢â€Å“Ã‚ÂºÃ¢â€Å“ÃƒÂºo de cÃ¢â€Å“ÃƒÂ³mera adicionado');
    }
    
    // ===== EVENTO DE CONTROLE DE ZOOM =====
    if (zoomSlider) {
        zoomSlider.addEventListener('input', function(e) {
            const zoomLevel = parseFloat(e.target.value);
            applyZoom(zoomLevel);
        });
        console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Event listener de zoom adicionado');
    }

    // ===== EVENTO DO BOTÃ¢â€Å“ÃƒÂ¢O X =====
    if(btnCloseScanner) {
        btnCloseScanner.addEventListener('click', function(e){
            console.log('Ãƒâ€ÃƒËœÃƒÂ® BotÃ¢â€Å“ÃƒÂºo X clicado');
            e.preventDefault();
            e.stopPropagation();
            stopScanner();
            bsModal.hide();
        });
        console.log('Ãƒâ€Ã‚Â£ÃƒÂ  Event listener do botÃ¢â€Å“ÃƒÂºo X adicionado');
    }

    // ===== LIMPAR QUANDO MODAL FECHAR =====
    modalEl.addEventListener('hidden.bs.modal', function(){
        console.log('Ã‚Â­Ã†â€™ÃƒÅ“Ã‚Â¬ Modal fechado');
        stopScanner();
        // Reset visual do frame
        const frame = document.querySelector('.scanner-frame');
        if(frame) {
            frame.style.borderColor = 'rgba(255, 255, 255, 0.8)';
            frame.style.boxShadow = '0 0 0 9999px rgba(0, 0, 0, 0.5)';
        }
    });
    
    console.log('Ã‚Â­Ã†â€™Ãƒâ€žÃƒÂ« === BARCODE SCANNER CONFIGURADO COM SUCESSO ===');
}
</script>

<?php
// Capturar o conteÃ¢â€Å“Ã¢â€¢â€˜do
$contentHtml = ob_get_clean();

// Criar arquivo temporÃ¢â€Å“ÃƒÂ­rio com o conteÃ¢â€Å“Ã¢â€¢â€˜do
$tempFile = __DIR__ . '/../../../temp_view_planilha_content_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

// Renderizar o layout
include __DIR__ . '/../layouts/app_wrapper.php';

// Limpar arquivo temporÃ¢â€Å“ÃƒÂ­rio
unlink($tempFile);
?>


