# Template Padrão do Relatório 14.1 (HTML + CSS)

Este diretório contém um HTML estático (`14-1.html`) e seu CSS (`14-1.css`) com a **estilização antiga** do relatório, e os **campos em sequência** (input1, input2, ...), sem nomes específicos. A ideia é que o seu PHP possa **repetir** este HTML por página e preencher os valores **pelo índice** do campo.

## Arquivos
- `14-1.html` — Uma página A4 com a estrutura do Relatório 14.1 e inputs sequenciais.
- `14-1.css` — CSS (cópia do estilo antigo) necessário para imprimir em A4 com fidelidade.
- `relatorio-14-1-bg.png` (opcional) — Imagem de fundo da página (exportada do PDF). Se existir, será usada como background visual.

## Ordem dos campos (sugestão)
Veja no topo do `14-1.html` um comentário listando a ordem (input1 … input38) e a que dado corresponde (CNPJ, Nº Relatório, etc.).

## Como usar no PHP
- Leia o arquivo `14-1.html` como string.
- Faça um `str_replace`/template engine para substituir `id="input1"`, `id="input2"`, … pelos valores desejados (ou injete `value="..."` via regex/DOM).
- Para múltiplas páginas, repita o bloco inteiro `<div class="a4"> ... </div>` por item.
- Para imprimir, abra o HTML no browser com o `14-1.css` no mesmo diretório e use `Ctrl+P`.

## Dica de performance
Se for gerar muitos relatórios, pré-carregue o arquivo `14-1.html` e faça apenas substituições em memória, sem reabrir o arquivo a cada página.

## Observação
Este template é **só front-end**; não depende de Composer ou de binários externos.
