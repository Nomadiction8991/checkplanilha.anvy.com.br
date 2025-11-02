<?php
require_once __DIR__ . '/../../../CRUD/READ/relatorio-14-1.php';

// Carregar template completo com CSS inline
$templatePath = __DIR__ . '/../../../relatorios/14-1.html';
$templateCompleto = '';
if (file_exists($templatePath)) {
    $templateCompleto = file_get_contents($templatePath);
    // Extrair apenas o conteúdo entre <!-- A4-START --> e <!-- A4-END -->
    $start = strpos($templateCompleto, '<!-- A4-START -->');
    $end   = strpos($templateCompleto, '<!-- A4-END -->');
    if ($start !== false && $end !== false && $end > $start) {
        $a4Block = trim(substr($templateCompleto, $start + strlen('<!-- A4-START -->'), $end - ($start + strlen('<!-- A4-START -->'))));
    } else {
        $a4Block = '';
    }
    
    // Extrair o <style> do template
    preg_match('/<style>(.*?)<\/style>/s', $templateCompleto, $matchesStyle);
    $styleContent = isset($matchesStyle[1]) ? $matchesStyle[1] : '';
} else {
    $a4Block = '';
    $styleContent = '';
}

$pageTitle = 'Relatório 14.1';
$backUrl = '../shared/menu.php?id=' . urlencode($id_planilha);
$headerActions = '<button id="btnPrint" class="btn-header-action" title="Imprimir"><i class="bi bi-printer"></i></button>';

// CSS customizado para a interface da aplicação (não do formulário)
$customCss = '

/* Container de páginas */
.paginas-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-bottom: 20px;
}

.pagina-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 15px;
    position: relative;
}

.pagina-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 2px solid #667eea;
}

/* --- IMPRESSÃO: mostrar somente as páginas (a4-viewport), 1 por folha, e esconder UI --- */
@media print {
    /* definir tamanho da página também no documento principal */
    @page { size: A4; margin: 0; }
    /* fundo branco geral e remoção de constraints do layout responsivo */
    html, body { background: #fff !important; }
    .app-container, .mobile-wrapper, .app-content { 
        background: #fff !important; 
        box-shadow: none !important; 
        padding: 0 !important; 
        margin: 0 !important; 
        width: auto !important; 
        max-width: none !important; 
        overflow: visible !important;
    }

    /* esconder elementos de UI comuns */
    .page-toolbar, .pagina-header, .pagina-actions, .toolbar-btn, .toolbar-counter,
    header, nav, aside, footer, .app-header, .app-sidebar, .btn-header-action { display: none !important; }

    /* limpar estilos de cartão e wrappers para impressão limpa */
    .paginas-container { display: block !important; margin: 0 !important; padding: 0 !important; width: 210mm !important; }
    .pagina-card { 
        display: block !important; 
        box-shadow: none !important; border: 0 !important; padding: 0 !important; margin: 0 !important; background: transparent !important; 
        break-inside: avoid-page !important; page-break-inside: avoid !important;
        width: 210mm !important;
    }

    /* cada viewport corresponde a uma página; evitar cortes e forçar quebras entre elas */
    .a4-viewport { 
        display: block !important; 
        margin: 0 auto !important; 
        padding: 0 !important; 
        background: #fff !important; 
        overflow: visible !important; 
        break-inside: avoid-page !important; page-break-inside: avoid !important;
        width: 210mm !important; min-height: 297mm !important;
    }
    .pagina-card:not(:last-child){ page-break-after: always !important; break-after: page !important; }
    .a4-viewport:not(:last-child){ page-break-after: always !important; break-after: page !important; }
    .a4-viewport + .a4-viewport { margin-top: 0 !important; }

    /* remover escala/posicionamento para impressão em tamanho real */
    .a4-scaled { transform: none !important; position: static !important; left: auto !important; top: auto !important; width: 210mm !important; height: 297mm !important; }

    /* garantir tamanho A4 do conteúdo impresso */
    iframe.a4-frame { 
        width: 210mm !important; 
        height: 297mm !important; 
        max-width: none !important; 
        max-height: none !important; 
        border: 0 !important; 
        box-shadow: none !important; 
        display: block !important; 
        margin: 0 auto !important; 
        overflow: visible !important;
    }
    /* reforço de quebra por iframe, garantindo 1 por página */
    .a4-viewport:not(:last-child) iframe.a4-frame { page-break-after: always !important; break-after: page !important; }
}

.pagina-numero {
    font-weight: 600;
    color: #667eea;
    font-size: 1rem;
}

.pagina-info {
    font-size: 0.85rem;
    color: #666;
}

/* Ações do cabeçalho da página */
.pagina-actions { display: flex; gap: 8px; }
.btn-expand {
    padding: 6px 10px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f1f5f9;
    color: #334155;
    cursor: pointer;
}
.btn-expand:hover { background: #e2e8f0; }

/* Botões de zoom no preview */


/* Barra fixa de navegação por páginas */
.page-toolbar {
    position: fixed;
    top: 68px; /* pequeno espaçamento do header */
    left: 50%;
    transform: translateX(-50%);
    width: 100%;
    max-width: 360px;
    z-index: 990;
    background: #ffffff;
    border: 1px solid #e5e7eb;
        border-radius: 0 0 12px 12px; /* sem cantos arredondados no topo */
    padding: 6px 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.toolbar-btn {
    padding: 8px 12px;
    border: none;
    border-radius: 8px;
    background: #667eea;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}
.toolbar-btn:disabled { background: #cbd5e1; cursor: not-allowed; }
.toolbar-counter { font-weight: 700; color: #334155; }

/* Wrapper da página A4 escalada */
.a4-viewport {
    position: relative;
    width: 100%;
    box-sizing: border-box; /* garantir que padding seja considerado corretamente */
    max-width: 100%;
    min-width: 200px; /* evita preview extremamente fino */
    overflow: hidden; /* preview mantém conteúdo contido */
    background: #f1f5f9;
    border-radius: 4px;
    display: flex;
    justify-content: center;
    align-items: flex-start; /* alinhar ao topo para permitir padding-top empurrar a miniatura para baixo */
/* pequeno espaçamento interno, 4px de margem solicitada no mobile */
}

.a4-scaled {
    /* Exibir o iframe exatamente no tamanho A4 (o iframe usa 210mm) e aplicar zoom via transform.
       Tornamos o wrapper inline-block e sem width% para que o scale seja aplicado sobre a largura real do A4 em px. */
    transform-origin: top left;
    transform: scale(0.5); /* valor inicial, será recalculado por fitAll() */
    display: inline-block;
    width: auto;
    height: auto;
    position:absolute;
    left:2.5%;
}

/* Forçar dimensões A4 reais para o iframe quando estiver dentro do wrapper .a4-scaled */
.a4-scaled iframe.a4-frame {
    width: 100%;
    aspect-ratio: 210 / 297; /* ou 1 / 1.414 */
    display: block;
    background: #fff;
}

/* Fundo da página (imagem do PDF) */
/* Viewer removido: estilos relacionados ao overlay/inline foram eliminados */
    position: sticky;
    top: env(safe-area-inset-top, 0px);
    z-index: 10;
    flex-shrink: 0;
}
.viewer-btn {
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f8fafc;
    color: #334155;
    cursor: pointer;
}
.viewer-btn.primary { background: #667eea; color: #fff; border-color: #667eea; }
.viewer-body {
    flex: 1;
    overflow: auto;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    overscroll-behavior: contain;
    position: relative;
}
.viewer-canvas {
    transform-origin: top center;
    width: 100%;
    min-height: 100%;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 16px;
}
.viewer-canvas iframe.a4-frame {
    display: block;
    border: 0;
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.viewer-body .viewer-canvas iframe.a4-frame {
    width: 100vw !important; /* ocupar 100% da largura da página HTML */
    height: 100vh !important; /* ocupar 100% da altura da página HTML */
    margin: 0; padding: 0; display: block;
}

/* Stage (quadro branco) que envolve o iframe e força o tamanho A4 por padrão */
.viewer-stage {
    display: inline-block;
    background: #fff;
    border-radius: 4px;
    padding: 0;
    box-sizing: content-box;
}
.viewer-stage iframe.a4-frame {
    /* Forçar tamanho de folha A4 (CSS mm) por padrão — navegador converte mm para px */
    width: 210mm !important;
    height: 297mm !important;
    max-width: 100%;
    max-height: 100%;
    display: block;
    min-width: 210mm; /* assegura dimensões reais A4 como mínimo */
    min-height: 297mm;
}

/* Inline expansion dentro do card (em vez de overlay full-screen) */
.pagina-card.expanded { z-index: 1500; position: relative; }
.pagina-card.expanded .a4-viewport {
    position: relative;
    max-width: calc(100% - 8px);
    margin: 4px; /* mantém 4px de espaçamento interno conforme solicitado */
    height: auto !important;
    max-height: 80vh; /* limita altura para não ocupar a tela inteira */
    overflow: auto; /* permitir scroll interno se necessário */
    background: transparent;
}
.pagina-card.expanded .a4-scaled { transform: none !important; }
.pagina-card.expanded iframe.a4-frame {
    transform: none !important;
    width: 90% !important; /* ocupar 90% da largura do widget/card */
    height: 80vh !important; /* altura alvo 80% da viewport */
    margin: 0 auto; /* centralizar */
    box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    border-radius: 6px;
}

/* Botão de fechar quando estiver expandido inline */
.pagina-card .inline-close-btn {
    margin-left: 8px;
    padding: 6px 8px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: #fff;
    cursor: pointer;
}

/* Mobile refinements */
@media (max-width: 768px) {
  .viewer-btn { padding: 10px 12px; font-size: 0.95rem; }
  .viewer-toolbar { gap: 6px; }
  .btn-expand { padding: 6px 10px; font-size: 0.9rem; }
  .page-toolbar { top: 64px; max-width: 92%; }
}

/* Ajustes específicos para telas pequenas para evitar preview muito fino */
@media (max-width: 480px) {
    .a4-viewport {
        /* em telas muito pequenas deixamos o fitAll controlar a altura; mantemos um padding menor */
        padding: 6px 4px;
        max-height: 80vh; /* permite que ocupe boa parte da tela sem ficar muito fino */
    }
    .a4-scaled { width: auto; }
    iframe.a4-frame { max-width: 100% !important; height: auto !important; }
    .pagina-card { padding: 10px; }
}
';

// (removed previous @media print rules - printing will open a clean window with the A4 content)

// Helper para preencher campos no template (suporta textarea e input)
if (!function_exists('r141_fillFieldById')) {
    function r141_fillFieldById(string $html, string $id, string $text): string {
        // Comportamento mínimo solicitado pelo usuário:
        // - NÃO alterar estrutura do template
        // - NÃO remover/alterar textareas
        // - Apenas inserir o valor vindo do banco dentro do <textarea> existente (ou em input value, se houver)

        // Trim e limitar comprimento para evitar injeção excessiva
        $text = trim((string)$text);
        $maxLen = 10000; // 10 KB por campo
        if (mb_strlen($text, 'UTF-8') > $maxLen) {
            $text = mb_substr($text, 0, $maxLen, 'UTF-8');
        }

        // Escape seguro para inserção em textarea ou value
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // 1) Preencher apenas se existir <textarea id="...">conteúdo</textarea>
        $patternTextarea = '/(<textarea\b[^>]*\bid=["\']' . preg_quote($id, '/') . '["\'][^>]*>)(.*?)(<\/textarea>)/si';
        $replaced = preg_replace($patternTextarea, '$1' . $escaped . '$3', $html, 1);
        if ($replaced !== null && $replaced !== $html) {
            return $replaced;
        }

        // 2) Se não existir textarea, tentar preencher atributo value de um <input id="..."> (caso raro)
        $patternInput = '/(<input\b[^>]*\bid=["\']' . preg_quote($id, '/') . '["\'][^>]*)(>)/i';
        $replacedInput = preg_replace_callback($patternInput, function($m) use ($escaped) {
            $prefix = $m[1];
            $suffix = $m[2];
            // se já existe value, substitui
            if (preg_match('/\bvalue\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', $prefix)) {
                return preg_replace('/\bvalue\s*=\s*(?:"[^"]*"|\'[^\']*\')/i', 'value="' . $escaped . '"', $prefix, 1) . $suffix;
            }
            // inserir value antes do fechamento
            return $prefix . ' value="' . $escaped . '"' . $suffix;
        }, $html, 1);
        if ($replacedInput !== null && $replacedInput !== $html) {
            return $replacedInput;
        }

        // Não modificar o template se textarea/input não existir
        return $html;
    }
}

ob_start();
?>

<?php if (count($produtos) > 0): ?>
<?php
    // Descobrir imagem de fundo, se existir
    $bgCandidates = [
        '/relatorios/relatorio-14-1-bg.png',
        '/relatorios/relatorio-14-1-bg.jpg',
        '/relatorios/relatorio-14-1-bg.jpeg',
        '/relatorios/relatorio-14-1.png',
        '/relatorios/relatorio-14-1.jpg',
        '/relatorios/ralatorio14-1.png',
        '/relatorios/ralatorio14-1.jpg',
    ];
    $bgUrl = '';
    $projectRoot = __DIR__ . '/../../../';
    foreach ($bgCandidates as $rel) {
        $abs = $projectRoot . ltrim($rel, '/');
        if (file_exists($abs)) { $bgUrl = $rel; break; }
    }
?>

<!-- valores-comuns removido conforme solicitado -->

<!-- Barra de navegação por páginas -->
<div class="page-toolbar">
    <button id="btnFirstPage" class="toolbar-btn" type="button" title="Primeira página"><i class="bi bi-skip-backward-fill"></i></button>
    <button id="btnPrevPage" class="toolbar-btn" type="button" title="Anterior"><i class="bi bi-chevron-left"></i></button>
    <span class="toolbar-counter"><span id="contadorPaginaAtual">1</span> / <span id="contadorTotalPaginas"><?php echo count($produtos); ?></span></span>
    <button id="btnNextPage" class="toolbar-btn" type="button" title="Próxima"><i class="bi bi-chevron-right"></i></button>
    <button id="btnLastPage" class="toolbar-btn" type="button" title="Última página"><i class="bi bi-skip-forward-fill"></i></button>
</div>

<!-- Container de páginas -->
<div class="paginas-container">
    <?php foreach($produtos as $index => $row): ?>
        <div class="pagina-card">
            <div class="pagina-header">
                <span class="pagina-numero">
                    <i class="bi bi-file-earmark-text"></i> Página <?php echo $index + 1; ?> de <?php echo count($produtos); ?>
                </span>
                <div class="pagina-actions">
                    <!-- Visualizar removido conforme solicitado -->
                </div>
            </div>
            
            <div class="a4-viewport">
                <div class="a4-scaled">
                    <?php
                        // Preencher dados do produto no template
                        $htmlPreenchido = $a4Block;
                        if (!empty($htmlPreenchido)) {
                            $dataEmissao = date('d/m/Y');
                            $descricaoBem = $row['descricao_completa'];

                            // Derivar alguns campos comuns adicionais
                            $administracao_auto = '';
                            if (!empty($comum_planilha)) {
                                $partesComum = array_map('trim', explode('-', $comum_planilha));
                                if (count($partesComum) >= 1) { $administracao_auto = $partesComum[0]; }
                            }
                            $setor_auto = isset($row['dependencia_descricao']) ? trim((string)$row['dependencia_descricao']) : '';
                            $local_data_auto = trim(($comum_planilha ?? '') . ' ' . $dataEmissao);

                            // Injetar valores nos campos por ID (textarea/input)
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input1', $dataEmissao);
                            if (!empty($setor_auto)) { $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input4', $setor_auto); }
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input5', $cnpj_planilha ?? '');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input6', $numero_relatorio_auto ?? '');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input7', $casa_oracao_auto ?? '');
                            if (!empty($descricaoBem)) { $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input8', $descricaoBem); }
                            if (!empty($local_data_auto)) { $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input16', $local_data_auto); }

                            // Opcional: injetar imagem de fundo se detectada
                            $htmlIsolado = $htmlPreenchido;
                            if (!empty($bgUrl)) {
                                $htmlIsolado = preg_replace('/(<div\s+class="a4"[^>]*>)/', '$1'.'<img class="page-bg" src="'.htmlspecialchars($bgUrl, ENT_QUOTES).'" alt="">', $htmlIsolado, 1);
                            }
                            // Montar srcdoc isolado com CSS do template
                            $styleInline = !empty($styleContent) ? $styleContent : '';
                            $srcdoc = '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
                                . '<style>html,body{margin:0;padding:0;background:#fff;} ' . $styleInline . '</style>'
                                . '</head><body>' . $htmlIsolado . '</body></html>';

                            // Gerar iframe de preview (Visualizar removido — iframe permanece como miniatura)
                            $title = 'Visualização da página ' . ($index + 1);
                            // adicionar allow-modals no sandbox para permitir que o iframe dispare dialogs/print em alguns navegadores
                            echo '<iframe class="a4-frame" data-page-index="' . $index . '" title="' . htmlspecialchars($title, ENT_QUOTES) . '" aria-label="' . htmlspecialchars($title, ENT_QUOTES) . '" tabindex="0" sandbox="allow-same-origin allow-scripts allow-forms allow-modals" style="width:210mm;height:297mm;" srcdoc="' . htmlspecialchars($srcdoc, ENT_QUOTES) . '"></iframe>';
                        } else {
                            echo '<div class="r141-root"><div class="a4"><p style="padding:10mm;color:#900">Template 14-1 não encontrado.</p></div></div>';
                        }
                    ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php else: ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Nenhum produto encontrado para impressão do relatório 14.1.
</div>
<?php endif;
$script = <<<JS
<script>
(function(){
    // Calcula px a partir de mm usando elemento temporário
    function mmToPx(mm){ const el=document.createElement('div'); el.style.position='absolute'; el.style.left='-9999px'; el.style.width=mm+'mm'; document.body.appendChild(el); const px=el.getBoundingClientRect().width; document.body.removeChild(el); return px; }

    function fitAll(){
        const a4w = mmToPx(210);
        const a4h = mmToPx(297);
        document.querySelectorAll('.a4-viewport').forEach(vp=>{
            const scaled = vp.querySelector('.a4-scaled');
            const frame = vp.querySelector('iframe.a4-frame');
            if(!scaled || !frame) return;
            const rect = vp.getBoundingClientRect();
            const style = getComputedStyle(vp);
            const paddingLeft = parseFloat(style.paddingLeft) || 0;
            const paddingRight = parseFloat(style.paddingRight) || 0;
            // largura útil dentro do viewport (inclui a área visível menos paddings)
            const available = rect.width - paddingLeft - paddingRight - 8; // 8px de margem de segurança
            let scale = available / a4w;
            if(!isFinite(scale) || scale <= 0) scale = 0.5;
            // limitar entre 0.25 e 1
            scale = Math.max(0.25, Math.min(1, scale));

            // definir dimensões reais do wrapper scaled para que o transform seja aplicado sobre valores previsíveis
            scaled.style.width = a4w + 'px';
            scaled.style.height = a4h + 'px';
            scaled.style.transformOrigin = 'top left';
            scaled.style.transform = 'scale(' + scale + ')';

            // Ajustar a altura do container para o A4 escalado (inclui padding-top)
            const paddingTop = parseFloat(style.paddingTop) || 0;
            const targetH = Math.round(a4h * scale + paddingTop + 4); // +4px folga
            vp.style.height = targetH + 'px';
            // assegurar overflow hidden para não mostrar fundo além do A4
            vp.style.overflow = 'hidden';
        });
    }

    const debounce = (fn,wait)=>{ let t; return function(){ clearTimeout(t); t=setTimeout(fn,wait); }; };
    window.addEventListener('resize', debounce(fitAll, 120));
    window.addEventListener('load', fitAll);
    document.addEventListener('DOMContentLoaded', fitAll);

    // --- paginação simples para navegar entre .pagina-card ---
    function setupPagination(){
        const pages = Array.from(document.querySelectorAll('.pagina-card'));
        if(pages.length === 0) return;
        let current = 0;
        const totalEl = document.getElementById('contadorTotalPaginas');
        const curEl = document.getElementById('contadorPaginaAtual');
        if(totalEl) totalEl.textContent = pages.length;
        function showPage(i){
            i = Math.max(0, Math.min(pages.length-1, i));
            current = i;
            if(curEl) curEl.textContent = (current+1);
            // rola suavemente para o topo do card
            pages[current].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        const first = document.getElementById('btnFirstPage');
        const prev  = document.getElementById('btnPrevPage');
        const next  = document.getElementById('btnNextPage');
        const last  = document.getElementById('btnLastPage');
        if(first) first.addEventListener('click', ()=> showPage(0));
        if(prev)  prev.addEventListener('click', ()=> showPage(current-1));
        if(next)  next.addEventListener('click', ()=> showPage(current+1));
        if(last)  last.addEventListener('click', ()=> showPage(pages.length-1));
        // inicializa na primeira
        showPage(0);
    }

    document.addEventListener('DOMContentLoaded', setupPagination);


    // Função global de impressão simplificada: apenas chama o print do navegador
    window.validarEImprimir = function(){
        window.print();
    };

})();
</script>
JS;

// Garantir que o botão de imprimir chame a função (listener delegado, mais robusto)
echo "<script>document.addEventListener('click', function(e){ var btn = e.target && e.target.closest && e.target.closest('#btnPrint'); if(btn){ e.preventDefault(); try{ console && console.log && console.log('print button clicked'); if(typeof window.validarEImprimir==='function'){ window.validarEImprimir(); } else { window.print(); } }catch(err){ console && console.error && console.error('print handler error', err); window.print(); } } });</script>\n";

echo $script;

?>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_relatorio_14_1_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
