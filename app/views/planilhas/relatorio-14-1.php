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
$backUrl = '../shared/menu-unificado.php?id=' . urlencode($id_planilha) . '&contexto=relatorio';
$headerActions = '
    <button id="btnPrint" class="btn-header-action" title="Imprimir"><i class="bi bi-printer"></i></button>
    <a href="../shared/menu-unificado.php?id=' . urlencode($id_planilha) . '&contexto=relatorio" class="btn-header-action" title="Menu">
        <i class="bi bi-list fs-5"></i>
    </a>
';

// CSS customizado para a interface da aplicação (não do formulário)
$customCss = '';
$customCssPath = __DIR__ . '/style/relatorio-14-1.css';
if (file_exists($customCssPath)) {
    $customCss = file_get_contents($customCssPath);
}

// (removed previous @media print rules - printing will open a clean window with the A4 content)

// Helper para preencher campos no template (suporta textarea e input)
if (!function_exists('r141_fillFieldById')) {
    function r141_fillFieldById(string $html, string $id, string $text): string {
        // Versão segura usando DOMDocument (substitui manipulação por regex)
        // - Não altera arquivos no disco
        // - Preenche <textarea id="..."> ou <input id="..."> quando existir
        // - Não faz fallbacks agressivos por padrão (mantém o template intacto em caso de ausência)

        $text = trim((string)$text);
        $maxLen = 10000;
        if (mb_strlen($text, 'UTF-8') > $maxLen) {
            $text = mb_substr($text, 0, $maxLen, 'UTF-8');
        }

        $prev = libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        // Wrap para garantir parse correto
        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html . '</body></html>';
        // Carregar o fragmento (suprimir warnings de HTML imperfeito)
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($doc);

        // 1) Procurar textarea com o id e preencher seu conteúdo
        $textarea = $xpath->query('//textarea[@id="' . $id . '"]')->item(0);
        if ($textarea) {
            // limpar nós filhos e inserir texto seguro
            while ($textarea->firstChild) { $textarea->removeChild($textarea->firstChild); }
            $textarea->appendChild($doc->createTextNode($text));
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            return r141_inner_html($doc->getElementsByTagName('body')->item(0));
        }

        // 2) Procurar input com o id e definir atributo value
        $input = $xpath->query('//input[@id="' . $id . '"]')->item(0);
        if ($input) {
            $input->setAttribute('value', $text);
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            return r141_inner_html($doc->getElementsByTagName('body')->item(0));
        }

        // 3) Não modificar se não encontrou elementos alvo
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $html;
    }
}

// helper: extrai innerHTML de um nó DOM
if (!function_exists('r141_inner_html')) {
    function r141_inner_html(\DOMNode $element): string {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }
        return $html;
    }
}

// helper: insere imagem de assinatura no lugar de um textarea
if (!function_exists('r141_insertSignatureImage')) {
    function r141_insertSignatureImage(string $html, string $textareaId, string $base64Image): string {
        if (empty($base64Image)) return $html;
        
        $prev = libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>' . $html . '</body></html>';
        $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($doc);
        
        // Encontrar o textarea
        $textarea = $xpath->query('//textarea[@id="' . $textareaId . '"]')->item(0);
        if ($textarea) {
            // Criar elemento img
            $img = $doc->createElement('img');
            $img->setAttribute('src', $base64Image);
            $img->setAttribute('alt', 'Assinatura');
            // Altura aproximada de 2 linhas de textarea; ajuste fino se necessário
            $img->setAttribute('style', 'max-width: 100%; height: auto; display: block; max-height: 9mm;');
            
            // Substituir textarea pela imagem
            $textarea->parentNode->replaceChild($img, $textarea);
            
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            return r141_inner_html($doc->getElementsByTagName('body')->item(0));
        }
        
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $html;
    }
}

ob_start();
?>

<?php if (count($produtos) > 0): ?>
<?php
    // Pré-carregar assinaturas para todos os produtos desta planilha em um único SELECT
    $assinaturasByProduto = [];
    try {
        $idsProdutos = array_column($produtos, 'id');
        if (!empty($idsProdutos)) {
            $placeholders = implode(',', array_fill(0, count($idsProdutos), '?'));
            $sqlAss = "SELECT * FROM assinaturas_14_1 WHERE id_planilha = ? AND id_produto IN ($placeholders)";
            $stmtAss = $conexao->prepare($sqlAss);
            // bind id_planilha + ids
            $bindIdx = 1;
            $stmtAss->bindValue($bindIdx++, $id_planilha, PDO::PARAM_INT);
            foreach ($idsProdutos as $pid) { $stmtAss->bindValue($bindIdx++, $pid, PDO::PARAM_INT); }
            $stmtAss->execute();
            $assinaturas = $stmtAss->fetchAll();
            foreach ($assinaturas as $ass) {
                $assinaturasByProduto[$ass['id_produto']] = $ass;
            }
        }
    } catch (Exception $e) {
        // mantém vazio em caso de erro
    }
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
                            // Preencher Data Emissão automaticamente com a data atual
                            $dataEmissao = date('d/m/Y');
                            $descricaoBem = $row['descricao_completa'];

                            // Derivar alguns campos comuns adicionais
                            $administracao_auto = '';
                            if (!empty($comum_planilha)) {
                                $partesComum = array_map('trim', explode('-', $comum_planilha));
                                if (count($partesComum) >= 1) { $administracao_auto = $partesComum[0]; }
                            }
                            $setor_auto = isset($row['dependencia_descricao']) ? trim((string)$row['dependencia_descricao']) : '';
                            // Não incluir data automática no campo de local/data — ficará apenas o valor comum da planilha
                            $local_data_auto = trim(($comum_planilha ?? ''));

                            // Injetar valores nos campos por ID (textarea/input)
                            // Preencher campo de Data Emissão com a data atual
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input1', $dataEmissao);
                            // Preencher Administração e Cidade dos novos campos da planilha
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input2', $administracao_planilha ?? '');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input3', $cidade_planilha ?? '');
                            // NÃO preencher automaticamente o setor (input4) por solicitação do usuário
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input5', $cnpj_planilha ?? '');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input6', $numero_relatorio_auto ?? '');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input7', $casa_oracao_auto ?? '');
                            if (!empty($descricaoBem)) { $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input8', $descricaoBem); }
                            // Preencher input16 com o valor comum da planilha seguido do placeholder de data
                            $local_data_with_placeholder = trim(($local_data_auto ?? '') . ' ' . '___/___/_____');
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input16', $local_data_with_placeholder);

                            // Preencher campos do doador/cônjuge/administrador a partir de assinaturas_14_1 (se existir)
                            $ass = $assinaturasByProduto[$row['id']] ?? null;
                            if ($ass) {
                                // Doador
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input17', (string)($ass['nome_doador'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input19', (string)($ass['endereco_doador'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input21', (string)($ass['cpf_doador'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input23', (string)($ass['rg_doador'] ?? ''));
                                // Cônjuge
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input18', (string)($ass['nome_conjuge'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input20', (string)($ass['endereco_conjuge'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input22', (string)($ass['cpf_conjuge'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input24', (string)($ass['rg_conjuge'] ?? ''));
                                // Administrador (termo de aceite)
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input27', (string)($ass['nome_administrador'] ?? ''));
                                // Repetir nome do doador no termo
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input29', (string)($ass['nome_doador'] ?? ''));

                                // Assinaturas: substituir textareas por imagens quando disponíveis
                                $sigAdmin   = (string)($ass['assinatura_administrador'] ?? '');
                                $sigDoador  = (string)($ass['assinatura_doador'] ?? '');
                                $sigConjuge = (string)($ass['assinatura_conjuge'] ?? '');
                                // Prefixar data URL se necessário
                                $ensureDataUrl = function($s){
                                    $s = trim((string)$s);
                                    if ($s === '') return $s;
                                    if (stripos($s, 'data:image') === 0) return $s;
                                    return 'data:image/png;base64,' . $s;
                                };
                                $sigAdmin   = $ensureDataUrl($sigAdmin);
                                $sigDoador  = $ensureDataUrl($sigDoador);
                                $sigConjuge = $ensureDataUrl($sigConjuge);

                                if (!empty($sigAdmin)) {
                                    $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input28', $sigAdmin);
                                }
                                if (!empty($sigDoador)) {
                                    // Campo de assinatura do doador na seção C
                                    $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input25', $sigDoador);
                                    // Campo de assinatura do doador no termo de aceite
                                    $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input30', $sigDoador);
                                }
                                if (!empty($sigConjuge)) {
                                    $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input26', $sigConjuge);
                                }
                            }

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

                            // Nota: removida gravação de debug em disco (ambiente remoto).
                            // Implementamos fallback de preview abaixo no helper quando necessário.

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
