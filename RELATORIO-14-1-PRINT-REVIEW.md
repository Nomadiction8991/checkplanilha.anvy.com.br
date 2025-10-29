# Revisão do fluxo de impressão do Relatório 14.1

Este documento resume os pontos fortes, pontos de atenção e melhorias propostas para o fluxo de preview e impressão do Relatório 14.1 no projeto `checkplanilha.anvy.com.br`.

Contexto principal analisado:
- View: `app/views/planilhas/relatorio-14-1.php`
- Template base: `relatorios/14-1.html`
- Estratégia atual: uma página com múltiplos iframes (`.a4-frame`) — um por produto — renderizados com `srcdoc` a partir do template A4; preview escalado na tela e CSS de impressão que oculta a UI e imprime apenas as áreas `.a4-viewport` (cada uma vira uma página).

## O que está funcionando bem (Prós)

- Visualização A4 fiel no preview:
  - Uso de wrapper `.a4-scaled` com transform para simular tamanho A4 e auto-fit responsivo via `mmToPx()`.
  - Cada página do relatório é isolada em um iframe com `srcdoc`, evitando conflitos de CSS/ID entre páginas.
- Fluxo de impressão simples:
  - Botão de imprimir disparando `window.print()` (sem popups), alinhado ao pedido do usuário.
  - CSS de impressão focado: esconde UI e imprime apenas `.a4-viewport`, com quebra entre páginas.
- Controle de navegação por páginas na UI (toolbar de paginação) sem interferir na impressão.
- Suporte a imagem de fundo do relatório (detectada em `relatorios/*`) injetada no HTML do template.
- Sandbox básico em iframes (`allow-same-origin allow-scripts allow-forms allow-modals`) para segurança e compatibilidade de dialog/print.

## Pontos de atenção (Contras / Riscos)

- Performance/consumo de memória:
  - Muitos iframes `srcdoc` simultâneos pesam em memória e DOM. Em planilhas grandes, o preview e a impressão podem ficar lentos.
- Fidelidade de impressão entre navegadores:
  - Sem regra `@page { size: A4; margin: 0 }` dentro do documento impresso (idealmente no CSS do conteúdo do iframe). Dependendo do navegador/driver, margens padrão da impressora podem cortar bordas.
  - Backgrounds podem não imprimir se o navegador tiver "background graphics" desativado; falta `print-color-adjust: exact`/`-webkit-print-color-adjust` no CSS do conteúdo do relatório.
- `srcdoc` e tamanho do HTML:
  - Conteúdo grande no `srcdoc` pode atingir limites internos do navegador em casos extremos.
- Injeção por `str_replace` em IDs (ex.: `id="input1"`):
  - Frágil a mudanças no template; fácil quebrar se o HTML mudar a ordem/atributos. Não valida existência/correção dos campos.
- Caminhos de imagem de fundo:
  - Descoberta via `$_SERVER['DOCUMENT_ROOT']` pode variar entre ambientes (dev/prod). Se o arquivo existir mas o webserver servir em caminho diferente, pode quebrar.
- Acessibilidade e semântica:
  - Toolbar e conteúdo são ok, mas preview e iframes múltiplos podem confundir leitores de tela; não há ARIA clara de relação página/contador.

## Melhorias rápidas (Quick wins)

- CSS de impressão dentro do conteúdo A4 (no template):
  - Adicionar no `relatorios/14-1.html`:
    - `@page { size: A4; margin: 0 }`.
    - `html, body { background: #fff }`.
    - `* { -webkit-print-color-adjust: exact; print-color-adjust: exact; }`.
  - Resultado: margens padronizadas, fundo/cores preservadas e menos dependência do CSS externo.

- Page breaks mais robustos:
  - No contêiner principal: `.a4-viewport { break-inside: avoid-page; }` já está; mantenha também `page-break-after: always` nas viewports exceto a última — já adicionado, mas revisar se a hierarquia não reintroduz margens ou sombras.

- Normalização de zoom no print:
  - Já removido o `transform` no `@media print`; conferir se nenhum outro seletor redefine `transform` ou `position` durante o print.

- Garantir carregamento do background:
  - Verificar `relatorios/relatorio-14-1-*.png/jpg` em produção e preferir caminho absoluto baseado na URL pública (ex.: `${BASE_URL}/relatorios/...`).

- Guardrails para a função de impressão:
  - Evitar múltiplos handlers: já há um listener delegado para `#btnPrint`; mantenha simples e idempotente.

## Melhorias de médio prazo

- Reduzir iframes na impressão:
  - Para imprimir, gerar um contêiner oculto no DOM com todas as páginas A4 em um único documento (sem iframes), mantendo os iframes só para preview. Isso melhora fidelidade e velocidade de impressão.
  - Estratégia: montar um HTML concatenando os blocos A4 (com CSS do template) e alternar a visibilidade no `@media print` para usar esse contêiner.

- Templating mais seguro que `str_replace`:
  - Adotar placeholders explícitos (ex.: `{{data_emissao}}`, `{{cnpj}}`) e substituir com uma mini engine (ex.: `strtr`) ou `Twig`/`Mustache`.
  - Alternativa sem libs: parsear com `DOMDocument` e setar por `getElementById()` (desde que IDs sejam únicos por documento — nos iframes são isolados).

- Lazy-loading e virtualização do preview:
  - Renderizar `srcdoc` dos iframes apenas quando entram no viewport (IntersectionObserver) e descartar quando saem, para reduzir custo em listas grandes.

- Padronizar variáveis e tokens de estilo:
  - Extrair cores, espaçamentos e medidas A4 para variáveis (CSS custom properties) e incluir em `style` do template.

- Automatizar geração de PDF (opcional):
  - Oferecer um botão "Gerar PDF" via `wkhtmltopdf`, `dompdf`, `mpdf` ou headless Chromium (Puppeteer). Útil para planilhas grandes e para distribuição.

## Melhorias de longo prazo / Arquitetura

- Modo preview vs modo impressão separados:
  - Preview com iframes escalados; impressão com HTML plano (sem iframes) para máxima fidelidade.
  - Facilitar também "Imprimir página atual" vs "Imprimir todas" (expondo um seletor de intervalo), sem popups.

- Cache e fragmentos pré-processados:
  - Pré-processar o template A4 (com CSS inline minificado) e armazenar um fragmento pronto para injetar por produto, reduzindo CPU por requisição.

- Testes cross-browser:
  - Montar checklist (Chrome, Firefox, Edge) em 100%, 80%, 50% de escala do sistema; com e sem "background graphics".

## Checklist de testes sugerido

- Visual:
  - Bordas, margens e alinhamento batem com o modelo A4 em Chrome/Firefox/Edge.
  - Background e cores imprimem quando "background graphics" está habilitado no navegador.
- Paginação:
  - Cada `.a4-viewport` vira 1 página, sem cortes no meio de blocos.
- Conteúdo:
  - Campos populados corretamente para todos os produtos.
- Performance:
  - Tempo para abrir o preview e chamar `Ctrl+P` com 10, 50, 100 páginas.
- Acessibilidade:
  - Foco e navegação por teclado na toolbar; leitor de tela não anuncia conteúdo redundante.

## Referências rápidas

- CSS Paged Media: `@page`, `size`, `margin`.
- `print-color-adjust` e `-webkit-print-color-adjust` para preservar cores/backgrounds.
- `break-inside: avoid-page` e `page-break-after: always` para controle de quebras.

---

Resumo: o fluxo atual atende bem ao preview e à impressão "sem popup". As principais alavancas de melhoria são a fidelidade e performance em cenários com muitas páginas: mover o CSS de impressão para dentro do template, reduzir iframes no caminho de impressão e considerar geração de PDF quando necessário. Isso deve trazer consistência entre navegadores e melhor UX em escala.
