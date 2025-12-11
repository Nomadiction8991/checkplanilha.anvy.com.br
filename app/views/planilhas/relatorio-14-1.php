<?php
require_once dirname(__DIR__, 2) . '/bootstrap.php';
 // Autenticação
require_once __DIR__ . '/../../../app/controllers/read/relatorio-14-1.php';

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
$backUrl = '../planilhas/view-planilha.php?id=' . urlencode($id_planilha);
$headerActions = '
    <div class="dropdown">
        <button class="btn-header-action" type="button" id="menuRelatorio" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-list fs-5"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="menuRelatorio">
            <li>
                <button id="btnPrint" class="dropdown-item">
                    <i class="bi bi-printer me-2"></i>Imprimir
                </button>
            </li>
            <li>
                <a href="assinatura-14-1.php?id=' . urlencode($id_planilha) . '" class="dropdown-item">
                    <i class="bi bi-pen me-2"></i>Assinaturas
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="../../../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Sair
                </a>
            </li>
        </ul>
    </div>
';

// CSS customizado para a interface da aplicação (não do formulário)
$customCss = '';
$customCssPath = __DIR__ . '/style/relatorio-14-1.css';
if (file_exists($customCssPath)) {
    $customCss .= file_get_contents($customCssPath);
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
            // Centraliza a assinatura horizontalmente
            $img->setAttribute('style', 'max-width: 100%; height: auto; display: block; max-height: 9mm; margin: 0 auto; object-fit: contain;');
            
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

                            // Preencher campos do administrador/acessor diretamente do produto (administrador_acessor_id)
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input27', (string)($row['administrador_nome'] ?? ''));
                            
                            // Assinatura do administrador
                            $sigAdmin = (string)($row['administrador_assinatura'] ?? '');
                            if (!empty($sigAdmin)) {
                                // Prefixar data URL se necessário
                                if (stripos($sigAdmin, 'data:image') !== 0) {
                                    $sigAdmin = 'data:image/png;base64,' . $sigAdmin;
                                }
                                $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input28', $sigAdmin);
                            }

                            // Preencher campos do doador/cônjuge diretamente do produto (doador_conjugue_id)
                            // Montagem de endereço completo do doador: logradouro, número, complemento, bairro - cidade/UF - CEP
                            $end_doador = trim(implode(' ', array_filter([
                                $row['doador_endereco_logradouro'] ?? '',
                                $row['doador_endereco_numero'] ?? ''
                            ])));
                            $end_doador_comp = trim(implode(' - ', array_filter([
                                $row['doador_endereco_complemento'] ?? '',
                                $row['doador_endereco_bairro'] ?? ''
                            ])));
                            $end_doador_local = trim(implode(' - ', array_filter([
                                trim(($row['doador_endereco_cidade'] ?? '')),
                                trim(($row['doador_endereco_estado'] ?? ''))
                            ])));
                            $end_doador_cep = trim($row['doador_endereco_cep'] ?? '');
                            // Formatação amigável: Partes principais separadas por vírgula; cidade-UF agrupadas; CEP no final se existir.
                            $partesEnd = [];
                            if ($end_doador) $partesEnd[] = $end_doador; // Rua + número
                            if ($end_doador_comp) $partesEnd[] = $end_doador_comp; // Complemento - Bairro
                            if ($end_doador_local) $partesEnd[] = $end_doador_local; // Cidade - UF
                            $endereco_doador_final = implode(', ', $partesEnd);
                            if ($end_doador_cep) {
                                $endereco_doador_final = rtrim($endereco_doador_final, ', ');
                                $endereco_doador_final .= ($endereco_doador_final ? ' - ' : '') . $end_doador_cep;
                            } else {
                                // Se não houver CEP, remover traço final se existir
                                $endereco_doador_final = rtrim($endereco_doador_final, ' -');
                            }

                            // Doador: nome, CPF, RG, Endereço
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input17', (string)($row['doador_nome'] ?? ''));
                            $cpfDoador = (string)($row['doador_cpf'] ?? '');
                            $rgDoadorOriginal = (string)($row['doador_rg'] ?? '');
                            $rgDoador = $rgDoadorOriginal;
                            if (empty($rgDoador) || (!empty($row['doador_rg_igual_cpf']) && $row['doador_rg_igual_cpf'])) {
                                // Fallback: RG recebe CPF quando marcado ou RG vazio
                                $rgDoador = $cpfDoador;
                            }
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input21', $cpfDoador);
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input23', $rgDoador);
                            // Endereço do doador
                            if (!empty($endereco_doador_final)) {
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input19', $endereco_doador_final);
                            }
                            // Repetir nome do doador no termo de aceite
                            $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input29', (string)($row['doador_nome'] ?? ''));
                            
                            // Assinatura do doador
                            $sigDoador = (string)($row['doador_assinatura'] ?? '');
                            if (!empty($sigDoador)) {
                                if (stripos($sigDoador, 'data:image') !== 0) {
                                    $sigDoador = 'data:image/png;base64,' . $sigDoador;
                                }
                                // Campo de assinatura do doador na seção C
                                $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input25', $sigDoador);
                                // Campo de assinatura do doador no termo de aceite
                                $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input30', $sigDoador);
                            }
                            
                            // Cônjuge (se o doador for casado)
                            if (!empty($row['doador_casado']) && $row['doador_casado'] == 1) {
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input18', (string)($row['doador_nome_conjuge'] ?? ''));
                                $cpfConj = (string)($row['doador_cpf_conjuge'] ?? '');
                                $rgConjOriginal = (string)($row['doador_rg_conjuge'] ?? '');
                                $rgConj = $rgConjOriginal;
                                if (empty($rgConj) || (!empty($row['doador_rg_conjuge_igual_cpf']) && $row['doador_rg_conjuge_igual_cpf'])) {
                                    $rgConj = $cpfConj;
                                }
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input22', $cpfConj);
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input24', $rgConj);
                                // Endereço do cônjuge (utiliza os mesmos campos do doador; se houver específicos, ajustar aqui)
                                $end_conj = trim(implode(' ', array_filter([
                                    $row['doador_endereco_logradouro'] ?? '',
                                    $row['doador_endereco_numero'] ?? ''
                                ])));
                                $end_conj_comp = trim(implode(' - ', array_filter([
                                    $row['doador_endereco_complemento'] ?? '',
                                    $row['doador_endereco_bairro'] ?? ''
                                ])));
                                $end_conj_local = trim(implode(' - ', array_filter([
                                    trim(($row['doador_endereco_cidade'] ?? '')),
                                    trim(($row['doador_endereco_estado'] ?? ''))
                                ])));
                                $end_conj_cep = trim($row['doador_endereco_cep'] ?? '');
                                $partesConj = [];
                                if ($end_conj) $partesConj[] = $end_conj;
                                if ($end_conj_comp) $partesConj[] = $end_conj_comp;
                                if ($end_conj_local) $partesConj[] = $end_conj_local;
                                $endereco_conjuge_final = implode(', ', $partesConj);
                                if ($end_conj_cep) {
                                    $endereco_conjuge_final = rtrim($endereco_conjuge_final, ', ');
                                    $endereco_conjuge_final .= ($endereco_conjuge_final ? ' - ' : '') . $end_conj_cep;
                                } else {
                                    // Se não houver CEP, remover traço final se existir
                                    $endereco_conjuge_final = rtrim($endereco_conjuge_final, ' -');
                                }
                                if (!empty($endereco_conjuge_final)) {
                                    $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input20', $endereco_conjuge_final);
                                }
                                
                                // Assinatura do cônjuge
                                $sigConjuge = (string)($row['doador_assinatura_conjuge'] ?? '');
                                if (!empty($sigConjuge)) {
                                    if (stripos($sigConjuge, 'data:image') !== 0) {
                                        $sigConjuge = 'data:image/png;base64,' . $sigConjuge;
                                    }
                                    $htmlPreenchido = r141_insertSignatureImage($htmlPreenchido, 'input26', $sigConjuge);
                                }
                            }

                            // Preencher campos de nota fiscal e marcar checkbox baseado em condicao_141
                            if (isset($row['condicao_14_1']) && ($row['condicao_14_1'] == 1 || $row['condicao_14_1'] == 3)) {
                                // Preencher campos de nota fiscal com novos nomes de colunas
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input9', (string)($row['nota_numero'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input10', (string)($row['nota_data'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input11', (string)($row['nota_valor'] ?? ''));
                                $htmlPreenchido = r141_fillFieldById($htmlPreenchido, 'input12', (string)($row['nota_fornecedor'] ?? ''));
                            }

                            // Opcional: injetar imagem de fundo se detectada
                            $htmlIsolado = $htmlPreenchido;
                            if (!empty($bgUrl)) {
                                $htmlIsolado = preg_replace('/(<div\s+class="a4"[^>]*>)/', '$1'.'<img class="page-bg" src="'.htmlspecialchars($bgUrl, ENT_QUOTES).'" alt="">', $htmlIsolado, 1);
                            }
                            // Montar srcdoc isolado com CSS do template
                            $styleInline = !empty($styleContent) ? $styleContent : '';
                            // CSS adicional: bloquear interação do usuário nos campos
                            $styleInline .= '\n.r141-root textarea, .r141-root input{pointer-events:none; -webkit-user-select:none; user-select:none; cursor: default;}';
                            $styleInline .= '\n.r141-root textarea{background:transparent; border:none; outline:none;}';
                            $styleInline .= '\n.r141-root input[disabled]{opacity:1; filter:none;}';
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

// Preparar dados dos produtos para JavaScript
  $produtosDataJS = json_encode(array_map(function($p){ 
     return [
         'id_produto' => (int)($p['id_produto'] ?? ($p['id'] ?? 0)),
         'condicao_14_1' => isset($p['condicao_14_1']) ? (int)$p['condicao_14_1'] : 0
     ]; 
 }, $produtos));

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

    // Paginação removida - todas as páginas serão exibidas em scroll

    // Marcar checkboxes 14.1 baseado em condicao_141 de cada produto
    const produtosData = PRODUTOS_DATA_PLACEHOLDER;
    
    function marcarCheckboxes(){
        document.querySelectorAll('iframe.a4-frame').forEach((iframe, idx) => {
            try {
                const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
                if(!iframeDoc) return;
                
                // Ajuste para novo nome da condição
                const condicao = produtosData[idx]?.condicao_14_1;
                if(!condicao) return;
                
                // IDs dos checkboxes no template: input13, input14, input15
                const check1 = iframeDoc.getElementById('input13'); // >5 anos com nota
                const check2 = iframeDoc.getElementById('input14'); // >5 anos sem nota
                const check3 = iframeDoc.getElementById('input15'); // <=5 anos com nota
                
                // Reset
                if(check1){ check1.checked = false; check1.removeAttribute('checked'); }
                if(check2){ check2.checked = false; check2.removeAttribute('checked'); }
                if(check3){ check3.checked = false; check3.removeAttribute('checked'); }

                // Set conforme condição
                if(condicao === 1 && check1){ check1.checked = true; check1.setAttribute('checked','checked'); }
                if(condicao === 2 && check2){ check2.checked = true; check2.setAttribute('checked','checked'); }
                if(condicao === 3 && check3){ check3.checked = true; check3.setAttribute('checked','checked'); }

                // Desabilitar interação nos campos dentro do iframe (inputs/textarea)
                Array.from(iframeDoc.querySelectorAll('textarea')).forEach(t => { try{ t.readOnly = true; t.setAttribute('readonly','readonly'); }catch(e){} });
                Array.from(iframeDoc.querySelectorAll('input')).forEach(inp => {
                    try{
                        if((inp.type||'').toLowerCase() === 'checkbox'){
                            inp.disabled = true; inp.setAttribute('disabled','disabled');
                        } else {
                            inp.readOnly = true; inp.setAttribute('readonly','readonly');
                        }
                    }catch(e){}
                });
            } catch(err) {
                console.error('Erro ao marcar checkboxes:', err);
            }
        });
    }
    
    // Executar após os iframes carregarem
    document.querySelectorAll('iframe.a4-frame').forEach(iframe => {
        iframe.addEventListener('load', marcarCheckboxes);
    });
    setTimeout(marcarCheckboxes, 500); // fallback


    // Função global de impressão simplificada: apenas chama o print do navegador
    window.validarEImprimir = function(){
        window.print();
    };

})();
</script>
JS;

// Substituir o placeholder pelos dados reais
$script = str_replace('PRODUTOS_DATA_PLACEHOLDER', $produtosDataJS, $script);

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

