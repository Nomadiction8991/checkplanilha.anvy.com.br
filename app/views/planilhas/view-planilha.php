<?php
$id_planilha = $_GET['id'] ?? null;
require_once __DIR__ . '/../../../CRUD/READ/view-planilha.php';

// Configurações da página
$pageTitle = htmlspecialchars($planilha['comum'] ?? 'Visualizar Planilha');
$backUrl = '../../../index.php';
$headerActions = '
    <a href="../shared/menu.php?id=' . $id_planilha . '" class="btn-header-action" title="Menu">
        <i class="bi bi-list fs-5"></i>
    </a>
';

// Iniciar buffer para capturar o conteúdo
ob_start();
?>

<style>
/* Estilos para o botão de microfone */
.mic-btn {
    border: none;
    background: transparent;
    color: inherit;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    transition: all 0.3s ease;
    position: relative;
}

.mic-btn:hover {
    background: rgba(0, 0, 0, 0.05);
}

.mic-btn.listening {
    animation: pulse 1.5s infinite;
    background: rgba(255, 255, 255, 0.2);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.mic-btn .material-icons-round {
    font-size: 24px;
    vertical-align: middle;
}

/* Cores das linhas baseadas no status */
.linha-pendente { background-color: #fff3cd; border-left: 4px solid #ffc107; }
.linha-checado { background-color: #d4edda; border-left: 4px solid #28a745; }
.linha-observacao { background-color: #fff3e0; border-left: 4px solid #ff9800; }
.linha-imprimir { background-color: #d1ecf1; border-left: 4px solid #17a2b8; }
.linha-dr { background-color: #f8d7da; border-left: 4px solid #dc3545; }
.linha-editado { background-color: #e2d9f3; border-left: 4px solid #9c27b0; }

/* Estilos para os botões de ação */
.btn-acao {
    padding: 0.25rem 0.5rem;
    border: none;
    background: transparent;
    cursor: pointer;
    opacity: 0.6;
    transition: opacity 0.2s;
}

.btn-acao:hover,
.btn-acao.active {
    opacity: 1;
}

.btn-acao svg {
    width: 24px;
    height: 24px;
}

.edicao-pendente {
    background: #f3e5f5;
    padding: 0.5rem;
    border-radius: 0.25rem;
    margin-bottom: 0.5rem;
    border-left: 3px solid #9c27b0;
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
                    Código do Produto
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" id="codigo" name="codigo" 
                           value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>" 
                           placeholder="Digite, fale ou escaneie o código...">
                    <button id="btnMic" class="btn btn-outline-secondary mic-btn" type="button" title="Falar código (Ctrl+M)" aria-label="Falar código" aria-pressed="false">
                        <span class="material-icons-round" aria-hidden="true">mic</span>
                    </button>
                    <button id="btnCam" class="btn btn-outline-secondary" type="button" title="Escanear código de barras" aria-label="Escanear código de barras">
                        <i class="bi bi-camera-video" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            
            <div class="accordion" id="filtrosAvancados">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltros">
                            <i class="bi bi-sliders me-2"></i>
                            Filtros Avançados
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
                                <label class="form-label" for="dependencia">Dependência</label>
                                <select class="form-select" id="dependencia" name="dependencia">
                                    <option value="">Todas</option>
                                    <?php foreach ($dependencia_options as $dep): ?>
                                    <option value="<?php echo htmlspecialchars($dep); ?>" 
                                        <?php echo ($filtro_dependencia ?? '') === $dep ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dep); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="checado" <?php echo ($filtro_status ?? '')==='checado'?'selected':''; ?>>Checados</option>
                                    <option value="observacao" <?php echo ($filtro_status ?? '')==='observacao'?'selected':''; ?>>Com Observação</option>
                                    <option value="etiqueta" <?php echo ($filtro_status ?? '')==='etiqueta'?'selected':''; ?>>Etiqueta para Imprimir</option>
                                    <option value="pendente" <?php echo ($filtro_status ?? '')==='pendente'?'selected':''; ?>>Pendentes</option>
                                    <option value="dr" <?php echo ($filtro_status ?? '')==='dr'?'selected':''; ?>>No DR</option>
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
</div>

<!-- Legenda -->
<div class="card mb-3">
    <div class="card-body p-2">
        <div class="d-flex flex-wrap gap-2 justify-content-center small">
            <span class="badge" style="background-color: #ffc107;">Pendente</span>
            <span class="badge" style="background-color: #28a745;">Checado</span>
            <span class="badge" style="background-color: #ff9800;">Observação</span>
            <span class="badge" style="background-color: #17a2b8;">Para Imprimir</span>
            <span class="badge" style="background-color: #dc3545;">DR</span>
            <span class="badge" style="background-color: #9c27b0;">Editado</span>
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
                
                if ($p['dr'] == 1) {
                    $classe = 'linha-dr';
                } elseif ($p['imprimir'] == 1 && $p['checado'] == 1) {
                    $classe = 'linha-imprimir';
                } elseif ($p['checado'] == 1) {
                    $classe = 'linha-checado';
                } elseif (!empty($p['observacoes'])) {
                    $classe = 'linha-observacao';
                } elseif ($tem_edicao) {
                    $classe = 'linha-editado';
                } else {
                    $classe = 'linha-pendente';
                }
                
                // Determinar quais botões mostrar
                $show_check = ($p['dr'] == 0 && $p['imprimir'] == 0 && $p['editado'] == 0);
                $show_imprimir = ($p['checado'] == 1 && $p['dr'] == 0 && $p['editado'] == 0);
                $show_dr = !($p['checado'] == 1 || $p['imprimir'] == 1 || $p['editado'] == 1);
                $show_obs = ($p['dr'] == 0);
                $show_edit = ($p['checado'] == 0 && $p['dr'] == 0);
            ?>
            <div class="list-group-item <?php echo $classe; ?>">
                <!-- Código -->
                <div class="codigo-produto">
                    <strong><?php echo htmlspecialchars($p['codigo']); ?></strong>
                </div>
                
                <!-- Edição Pendente -->
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
                
                <!-- Informações -->
                <div class="info-produto">
                    <strong>Nome:</strong> <?php echo htmlspecialchars($p['nome']); ?><br>
                    <?php if (!empty($p['dependencia'])): ?>
                    <strong>Dep:</strong> <?php echo htmlspecialchars($p['dependencia']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($p['observacoes'])): ?>
                    <strong>Obs:</strong> <?php echo htmlspecialchars($p['observacoes']); ?><br>
                    <?php endif; ?>
                </div>
                
                <!-- Ações -->
                <div class="acao-container">
                    <!-- Check -->
                    <?php if ($show_check): ?>
                    <form method="POST" action="../../../CRUD/UPDATE/check-produto.php" style="display: inline;" class="check-form">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="checado" value="<?php echo $p['checado'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina ?? 1; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia ?? ''); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status ?? ''); ?>">
                        <button type="submit" class="btn-acao btn-check <?php echo $p['checado'] == 1 ? 'active' : ''; ?>" title="Check">
                            <i class="bi bi-check-square-fill" style="color: #28a745; font-size: 24px;"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- DR -->
                    <?php if ($show_dr): ?>
                    <form method="POST" action="../../../CRUD/UPDATE/dr-produto.php" style="display: inline;" onsubmit="return confirmarDR(this, <?php echo $p['dr']; ?>)">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="dr" value="<?php echo $p['dr'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina ?? 1; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia ?? ''); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status ?? ''); ?>">
                        <button type="submit" class="btn-acao btn-dr <?php echo $p['dr'] == 1 ? 'active' : ''; ?>" title="DR">
                            <i class="bi bi-bookmark-fill" style="color: #dc3545; font-size: 24px;"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Etiqueta -->
                    <?php if ($show_imprimir): ?>
                    <form method="POST" action="../../../CRUD/UPDATE/etiqueta-produto.php" style="display: inline;" onsubmit="return confirmarImprimir(this, <?php echo $p['imprimir']; ?>)">
                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                        <input type="hidden" name="id_planilha" value="<?php echo $id_planilha; ?>">
                        <input type="hidden" name="imprimir" value="<?php echo $p['imprimir'] ? '0' : '1'; ?>">
                        <input type="hidden" name="pagina" value="<?php echo $pagina ?? 1; ?>">
                        <input type="hidden" name="nome" value="<?php echo htmlspecialchars($filtro_nome ?? ''); ?>">
                        <input type="hidden" name="dependencia" value="<?php echo htmlspecialchars($filtro_dependencia ?? ''); ?>">
                        <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo ?? ''); ?>">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($filtro_status ?? ''); ?>">
                        <button type="submit" class="btn-acao btn-etiqueta <?php echo $p['imprimir'] == 1 ? 'active' : ''; ?>" title="Etiqueta">
                            <i class="bi bi-printer-fill" style="color: #17a2b8; font-size: 24px;"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    
                    <!-- Observação -->
                    <?php if ($show_obs): ?>
                    <a href="../produtos/observacao-produto.php?id_produto=<?php echo $p['id']; ?>&id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina ?? 1; ?>&nome=<?php echo urlencode($filtro_nome ?? ''); ?>&dependencia=<?php echo urlencode($filtro_dependencia ?? ''); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>"
                       class="btn-acao btn-observacao <?php echo !empty($p['observacoes']) ? 'active' : ''; ?>" title="Observação">
                        <i class="bi bi-chat-square-text-fill" style="color: #ff9800; font-size: 24px;"></i>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Editar -->
                    <?php if ($show_edit): ?>
                    <a href="../produtos/editar-produto.php?id_produto=<?php echo $p['id']; ?>&id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina ?? 1; ?>&nome=<?php echo urlencode($filtro_nome ?? ''); ?>&dependencia=<?php echo urlencode($filtro_dependencia ?? ''); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo ?? ''); ?>&status=<?php echo urlencode($filtro_status ?? ''); ?>"
                       class="btn-acao btn-editar <?php echo $tem_edicao ? 'active' : ''; ?>" title="Editar">
                        <i class="bi bi-pencil-fill" style="color: #9c27b0; font-size: 24px;"></i>
                    </a>
                    <?php endif; ?>
                </div>
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

<!-- Paginação -->
<?php if (isset($total_paginas) && $total_paginas > 1): ?>
<nav aria-label="Navegação de página" class="mt-3">
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
        return confirm('Tem certeza que deseja marcar este produto como DR? Esta ação irá limpar as observações e desmarcar para impressão.');
    } else {
        return confirm('Tem certeza que deseja desmarcar este produto do DR?');
    }
}

function confirmarImprimir(form, imprimirAtual) {
    if (imprimirAtual == 0) {
        return confirm('Tem certeza que deseja marcar este produto para impressão?');
    } else {
        return confirm('Tem certeza que deseja desmarcar este produto da impressão?');
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
    const el = document.querySelector('input[placeholder*="código" i],input[placeholder*="codigo" i]');
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
        micBtn.title = 'Reconhecimento de voz não suportado neste navegador';
        const iconNF = micBtn.querySelector('.material-icons-round');
        if(iconNF){ iconNF.textContent = 'mic_off'; }
        micBtn.addEventListener('click', () => {
            alert('Reconhecimento de voz não é suportado neste navegador. Use o botão de câmera ou digite o código.');
        });
        return;
  }

  const DIGITOS = {
    "zero":"0","um":"1","uma":"1","dois":"2","duas":"2","três":"3","tres":"3",
    "quatro":"4","cinco":"5","seis":"6","meia":"6","sete":"7","oito":"8","nove":"9"
  };
  const SINAIS = {
    "tracinho":"-","hífen":"-","hifen":"-","menos":"-",
    "barra":"/","barra invertida":"\\","contrabarra":"\\","invertida":"\\",
    "ponto":".","vírgula":",","virgula":",","espaço":" "
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
      alert('Campo de código não encontrado.');
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
      alert('Não entendi o código. Tente soletrar: "um dois três"…');
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

<!-- Modal para escanear código de barras -->
<div class="modal fade" id="barcodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Escanear código de barras</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="scanner-container" style="width:100%; min-height:240px; background:#000; position:relative; overflow:hidden;"></div>
                <div class="small text-muted mt-2">Aponte a câmera para o código de barras. Suporta EAN, CODE128, CODE39, UPC…</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
  
</div>

<!-- Quagga2 para leitura de códigos de barras -->
<script src="https://unpkg.com/@ericblade/quagga2/dist/quagga.min.js"></script>
<script>
(function(){
    const camBtn = document.getElementById('btnCam');
    const modalEl = document.getElementById('barcodeModal');
    if(!camBtn || !modalEl || !window.bootstrap) return;
    const codigoInput = document.getElementById('codigo');
    const form = codigoInput ? (codigoInput.form || document.querySelector('form')) : document.querySelector('form');
    const scannerContainer = document.getElementById('scanner-container');
    const bsModal = new bootstrap.Modal(modalEl);
    let scanning = false;
    let lastCode = '';

    function stopScanner(){
        try{ Quagga.stop(); }catch(e){}
        scanning = false;
    }

    function startScanner(){
        if(scanning) return;
        scanning = true;
        Quagga.init({
            inputStream: {
                type: 'LiveStream',
                target: scannerContainer,
                constraints: { facingMode: 'environment' }
            },
            decoder: { readers: ['ean_reader','code_128_reader','code_39_reader','upc_reader','upc_e_reader','ean_8_reader'] },
            locate: true
        }, function(err){
            if(err){
                alert('Não foi possível acessar a câmera: ' + err.message);
                scanning = false;
                return;
            }
            Quagga.start();
        });

        Quagga.offDetected();
        Quagga.onDetected(function(result){
            if(!result || !result.codeResult || !result.codeResult.code) return;
            const code = result.codeResult.code.trim();
            if(!code || code === lastCode) return;
            lastCode = code;
            stopScanner();
            bsModal.hide();
            if(codigoInput){
                codigoInput.value = code;
                codigoInput.dispatchEvent(new Event('input',{bubbles:true}));
                codigoInput.dispatchEvent(new Event('change',{bubbles:true}));
            }
            if(form){ form.requestSubmit ? form.requestSubmit() : form.submit(); }
        });
    }

    camBtn.addEventListener('click', function(){
        lastCode = '';
        bsModal.show();
        setTimeout(startScanner, 250);
    });

    modalEl.addEventListener('hidden.bs.modal', function(){
        stopScanner();
    });
})();
</script>

<?php
// Capturar o conteúdo
$contentHtml = ob_get_clean();

// Criar arquivo temporário com o conteúdo
$tempFile = __DIR__ . '/../../../temp_view_planilha_content_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;

// Renderizar o layout
include __DIR__ . '/../layouts/app-wrapper.php';

// Limpar arquivo temporário
unlink($tempFile);
?>
