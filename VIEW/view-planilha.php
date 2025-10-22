<?php
$id_planilha = $_GET['id'] ?? null; // MUDAR PARA 'id'
/*
if (!$id_planilha) {
    header('Location: ../index.php');
    exit;
}*/

require_once '../CRUD/READ/view-planilha.php';


?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Planilha - <?php echo htmlspecialchars($planilha['descricao']); ?></title>
    <link rel="stylesheet" href="../STYLE/base.css">
    <link rel="stylesheet" href="../STYLE/view-planilha.css">
    
</head>
<body>
    <header class="cabecalho">
        <section class="titulo">
            <a href="../index.php" class="voltar">
                <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#FFFFFF"><path d="m274-450 248 248-42 42-320-320 320-320 42 42-248 248h526v60H274Z"/></svg>
            </a>
            <h1><?php echo htmlspecialchars($planilha['comum']); ?></h1>
        </section>
        <section class="acoes">
            <button id="btnMic" class="icon-btn" type="button" aria-label="Falar código" title="Falar código (Ctrl+M)">🎤</button>
            <a href="menu.php?id=<?php echo $id_planilha; ?>"> <!-- MUDAR PARA id -->
                <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#FFFFFF"><path d="M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z"/></svg>
            </a>
        </section>
    </header>

    <section class="conteudo">
        <!-- Filtros -->
        <div class="filtros-container">
            <form method="GET" class="form-filtros">
                <input type="hidden" name="id" value="<?php echo $id_planilha; ?>">
                <div class="campo-codigo">
                    <input type="text" id="codigo" name="codigo" value="<?php echo htmlspecialchars($filtro_codigo); ?>" placeholder="Pesquisar código">
                    <button type="submit" class="btn-filtrar" title="Filtrar">
                        <svg xmlns="http://www.w3.org/2000/svg" height="20px" viewBox="0 -960 960 960" width="20px" fill="#FFFFFF">
                            <path d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z"/>
                        </svg>
                    </button>
                </div>
                <details class="filtros-avancados">
                    <summary>Filtros Avançados</summary>
                    <div class="filtros-content">
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
                                <option value="editado" <?php echo $filtro_status==='editado'?'selected':''; ?>>Editados</option>
                            </select>
                        </div>
                    </div>
                </details>
            </form>
        </div>

        <!-- Tabela de Produtos -->
        <div class="tabela-container">
            <table>
                <thead>
                    <tr>
                        <th>Produto</th>
                    </tr>
                </thead>
                <tbody>
<?php if ($produtos): ?>
    <?php foreach ($produtos as $p): 
        // Determinar a classe com base nos status - USANDO A NOVA COLUNA editado
        $classe = '';
        $tem_edicao = $p['editado'] == 1;
        
        // ORDEM DE PRIORIDADE CORRETA
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
        
        // Determinar quais botões mostrar - USANDO A NOVA COLUNA editado
        $show_check = ($p['dr'] == 0 && $p['imprimir'] == 0 && $p['editado'] == 0);
        $show_imprimir = ($p['checado'] == 1 && $p['dr'] == 0 && $p['editado'] == 0);
        $show_dr = !($p['checado'] == 1 || $p['imprimir'] == 1 || $p['editado'] == 1);
        $show_obs = ($p['dr'] == 0);
        $show_edit = ($p['checado'] == 0 && $p['dr'] == 0);
    ?>
    <tr class="<?php echo $classe; ?>">
                      
                            <td>
                                <!-- Código do produto -->
                                <div class="codigo-produto">
                                    <strong><?php echo htmlspecialchars($p['codigo']); ?></strong>
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
                                </div>
                                
                                <!-- Ações -->
                                <div class="acao-container">
                                    <!-- Checkbox -->
                                    <?php if ($show_check): ?>
                                    <form method="POST" action="../CRUD/UPDATE/check-produto.php" style="display: inline;">
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
                                    <form method="POST" action="../CRUD/UPDATE/dr-produto.php" style="display: inline;" onsubmit="return confirmarDR(this, <?php echo $p['dr']; ?>)">
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
                                    <form method="POST" action="../CRUD/UPDATE/etiqueta-produto.php" style="display: inline;" onsubmit="return confirmarImprimir(this, <?php echo $p['imprimir']; ?>)">
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
<!-- Observação -->
<?php if ($show_obs): ?>
<a href="observacao-produto.php?id_produto=<?php echo $p['id']; ?>&id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"
   class="btn-acao btn-observacao <?php echo !empty($p['observacoes']) ? 'active' : ''; ?>">
    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#F19E39"><path d="M320-440h320v-80H320v80Zm0 120h320v-80H320v80Zm0 120h200v-80H320v80ZM240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h320l240 240v480q0 33-23.5 56.5T720-80H240Zm280-520v-200H240v640h480v-440H520ZM240-800v200-200 640-640Z"/></svg>
</a>
<?php endif; ?>
                                    
                                    <!-- Editar -->
<?php if ($show_edit): ?>
<a href="editar-produto.php?id_produto=<?php echo $p['id']; ?>&id=<?php echo $id_planilha; ?>&pagina=<?php echo $pagina; ?>&nome=<?php echo urlencode($filtro_nome); ?>&dependencia=<?php echo urlencode($filtro_dependencia); ?>&filtro_codigo=<?php echo urlencode($filtro_codigo); ?>&status=<?php echo urlencode($filtro_status); ?>"
   class="btn-acao btn-editar <?php echo $tem_edicao ? 'active' : ''; ?>">
    <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#A76CFF"><path d="M200-200h57l391-391-57-57-391 391v57Zm-80 80v-170l528-527q12-11 26.5-17t30.5-6q16 0 31 6t26 18l55 56q12 11 17.5 26t5.5 30q0 16-5.5 30.5T817-647L290-120H120Zm640-584-56-56 56 56Zm-141 85-28-29 57 57-29-28Z"/></svg>
</a>
<?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" class="sem-resultados">Nenhum produto encontrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

 <!-- Paginação Melhorada -->
<?php if ($total_paginas > 1): ?>
<div class="paginacao">
    <?php if ($pagina > 1): ?>
        <a href="?<?php echo http_build_query(array_merge(['id' => $id_planilha], $_GET, ['pagina' => $pagina - 1])); ?>" class="pagina-item pagina-anterior">
            ‹ Anterior
        </a>
    <?php endif; ?>
    
    <?php 
    // Mostrar apenas algumas páginas ao redor da atual
    $inicio = max(1, $pagina - 2);
    $fim = min($total_paginas, $pagina + 2);
    
    for ($i = $inicio; $i <= $fim; $i++): 
    ?>
        <?php if ($i == $pagina): ?>
            <span class="pagina-atual"><?php echo $i; ?></span>
        <?php else: ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" class="pagina-item">
                <?php echo $i; ?>
            </a>
        <?php endif; ?>
    <?php endfor; ?>
    
    <?php if ($pagina < $total_paginas): ?>
        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" class="pagina-item pagina-proxima">
            Próxima ›
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>
    </section>

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
    </script>
    <script>
(() => {
  // ======== CONFIG RÁPIDA (ajuste se seus IDs forem diferentes) ========
  const POSSIVEIS_IDS_INPUT = ["cod", "codigo", "code", "productCode", "busca", "search", "q"];
  const POSSIVEIS_BOTOES    = ["btnBuscar", "btnPesquisa", "btnSearch"];
  const MENU_BUTTON_ID      = "btnMenu"; // se quiser injetar o mic ao lado de um botão específico

  // ======== LOCALIZA ELEMENTOS ========
  function encontraInputCodigo(){
    // por id
    for(const id of POSSIVEIS_IDS_INPUT){
      const el = document.getElementById(id);
      if(el) return el;
    }
    // por name
    for(const name of ["cod","codigo","code","productCode","q","busca","search"]){
      const el = document.querySelector(`input[name="${name}"]`);
      if(el) return el;
    }
    // por placeholder
    const el = document.querySelector('input[placeholder*="código" i],input[placeholder*="codigo" i]');
    return el || null;
  }
  function encontraBotaoPesquisar(input){
    // preferir botões conhecidos
    for(const id of POSSIVEIS_BOTOES){
      const b = document.getElementById(id);
      if(b) return b;
    }
    // botão submit do mesmo form
    if(input && input.form){
      const b = input.form.querySelector('button[type="submit"],input[type="submit"]');
      if(b) return b;
    }
    // qualquer botão de submit na página
    return document.querySelector('button[type="submit"],input[type="submit"]');
  }
  function injetaMicAoLadoDoMenu(micBtn){
    const menuBtn = document.getElementById(MENU_BUTTON_ID);
    if(menuBtn && menuBtn.parentElement){
      menuBtn.parentElement.insertBefore(micBtn, menuBtn.nextSibling);
      return;
    }
    // fallback: coloca no primeiro header encontrado
    const header = document.querySelector('.header, header');
    if(header) header.appendChild(micBtn);
  }

  // ======== CRIA / POSICIONA O BOTÃO ========
  let micBtn = document.getElementById('btnMic');
  if(!micBtn){
    micBtn = document.createElement('button');
    micBtn.id = 'btnMic';
    micBtn.className = 'icon-btn';
    micBtn.type = 'button';
    micBtn.title = 'Falar código (Ctrl+M)';
    micBtn.setAttribute('aria-label','Falar código');
    micBtn.textContent = '🎤';
    injetaMicAoLadoDoMenu(micBtn);
  }

  // ======== SUPORTE À API DE VOZ ========
  const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
  if(!SR){
    // sem suporte: esconde o botão
    micBtn.style.display = 'none';
    return;
  }

  // ======== NORMALIZA TRANSCRIÇÃO PARA CÓDIGO ========
  // Suporta "um dois três", "zero zero cinco", "tracinho", "hífen", "barra", "ponto".
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
    // tenta primeiro pegar números "crus" da fala
    let direto = trans.replace(/[^\d\-./,\\ ]+/g,'').trim();
    direto = direto.replace(/\s+/g,''); // remove espaços se for sequência
    if(/\d/.test(direto)) return direto;

    // senão, mapeia palavras -> dígitos/sinais
    const out = [];
    for(const raw of trans.toLowerCase().split(/\s+/)){
      const w = raw.normalize('NFD').replace(/\p{Diacritic}/gu,''); // remove acento
      if(DIGITOS[w]) out.push(DIGITOS[w]);
      else if(SINAIS[w]) out.push(SINAIS[w]);
      else if(/^\d+$/.test(w)) out.push(w);
    }
    return out.join('');
  }

  // ======== DISPARO DA BUSCA ========
  async function preencherEEnviar(codigo){
    const input = encontraInputCodigo();
    if(!input){
      alert('Campo de código não encontrado. Ajuste os IDs em POSSIVEIS_IDS_INPUT.');
      return;
    }
    // preencher
    input.focus();
    input.value = codigo;

    // tentar acionar eventos que sua página já usa (keyup/change)
    input.dispatchEvent(new Event('input', {bubbles:true}));
    input.dispatchEvent(new Event('change', {bubbles:true}));

    // preferir clicar no botão de busca; senão, submit no form; por fim, tecla Enter.
    const btn = encontraBotaoPesquisar(input);
    if(btn){
      btn.click();
      return;
    }
    if(input.form){
      input.form.requestSubmit ? input.form.requestSubmit() : input.form.submit();
      return;
    }
    // fallback: envia Enter no input (útil em buscas por keypress)
    const ev = new KeyboardEvent('keydown', {key:'Enter', code:'Enter', bubbles:true});
    input.dispatchEvent(ev);
  }

  // ======== CONTROLE DE RECONHECIMENTO ========
  const rec = new SR();
  rec.lang = 'pt-BR';
  rec.continuous = false;
  rec.interimResults = false;
  rec.maxAlternatives = 3;

  function startListening(){
    try{
      rec.start();
      micBtn.classList.add('listening');
    }catch(e){
      // em alguns navegadores, start() lança se já estiver rodando
    }
  }
  function stopListening(){
    try{ rec.stop(); }catch(e){}
    micBtn.classList.remove('listening');
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
    // erros comuns: not-allowed (permissão), no-speech, aborted
    if(e.error === 'not-allowed') alert('Permita o acesso ao microfone para usar a busca por voz.');
  };
  rec.onend = () => micBtn.classList.remove('listening');

  // clique do botão e atalho de teclado
  micBtn.addEventListener('click', () => {
    // se já está ouvindo, parar; senão, começar
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

</body>
</html>