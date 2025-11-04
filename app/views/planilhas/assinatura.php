<?php
require_once '../../../auth.php'; // Autenticação
// Página dedicada para captura de assinatura em modo paisagem.
// Salva o resultado em localStorage['signature_temp'] e retorna via history.back()

ob_start();
?>

<div style="padding:12px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
        <h5>Assinatura (modo paisagem)</h5>
        <div>
            <button id="btnFull" class="btn btn-primary btn-sm">Iniciar em paisagem</button>
            <button id="btnSave" class="btn btn-success btn-sm">Salvar</button>
            <button id="btnClear" class="btn btn-warning btn-sm">Limpar</button>
            <button id="btnCancel" class="btn btn-danger btn-sm">Cancelar</button>
        </div>
    </div>

    <div id="canvasWrapper" style="width:100%; height:calc(100vh - 80px); overflow:auto; -webkit-overflow-scrolling:touch; display:flex; align-items:center; justify-content:center; background:#f8f9fa;">
        <canvas id="sign_canvas" style="background:#fff; border:1px solid #ddd; display:block;"></canvas>
    </div>

    <div class="mt-2 small text-muted">Dica: após salvar você será levado de volta à página anterior.</div>
</div>

<script>
(function(){
    const canvas = document.getElementById('sign_canvas');
    const wrapper = document.getElementById('canvasWrapper');
    const btnFull = document.getElementById('btnFull');
    const btnSave = document.getElementById('btnSave');
    const btnClear = document.getElementById('btnClear');
    const btnCancel = document.getElementById('btnCancel');
    let signaturePad = null;

    function resizeCanvasForLandscape(width) {
        const vw = window.innerWidth;
        const vh = window.innerHeight;
        // Desired width may be larger than viewport; wrapper is scrollable
        const cssW = width || Math.max(1200, Math.floor(Math.max(vw, vh) * 1.2));
        const cssH = Math.max(90, Math.floor(cssW / 8));
        canvas.style.width = cssW + 'px';
        canvas.style.height = cssH + 'px';
        const dpr = window.devicePixelRatio || 1;
        canvas.width = Math.floor(cssW * dpr);
        canvas.height = Math.floor(cssH * dpr);
        const ctx = canvas.getContext('2d');
        try{ ctx.setTransform(1,0,0,1,0,0); } catch(e){}
        ctx.scale(dpr, dpr);
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0,0,cssW,cssH);
        ctx.lineWidth = 2; ctx.lineCap = 'round';
    }

    function initSignaturePad() {
        if (typeof SignaturePad === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js';
            s.onload = function(){
                signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'black' });
            };
            document.head.appendChild(s);
        } else {
            signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'black' });
        }
    }

    // Try to enter fullscreen and lock orientation on user gesture
    async function enterFullscreenAndLock(){
        try{
            if (document.documentElement.requestFullscreen) await document.documentElement.requestFullscreen();
            if (screen && screen.orientation && screen.orientation.lock) {
                try{ await screen.orientation.lock('landscape'); } catch(e){}
            }
        }catch(e){ console.warn('fullscreen/orientation failed', e); }
    }

    btnFull.addEventListener('click', async function(){
        await enterFullscreenAndLock();
        resizeCanvasForLandscape();
        initSignaturePad();
        // scroll to center
        try{ wrapper.scrollLeft = Math.max(0, (canvas.clientWidth - wrapper.clientWidth)/2); }catch(e){}
    });

    btnClear.addEventListener('click', function(){ if (signaturePad) signaturePad.clear(); try{ const ctx=canvas.getContext('2d'); ctx.clearRect(0,0,canvas.width,canvas.height); resizeCanvasForLandscape(canvas.clientWidth); }catch(e){} });

    btnCancel.addEventListener('click', function(){
        // clear temporary storage and go back
        try{ localStorage.removeItem('signature_temp'); }catch(e){}
        history.back();
    });

    btnSave.addEventListener('click', function(){
        let data = null;
        if (signaturePad) {
            if (signaturePad.isEmpty()) data = null; else data = signaturePad.toDataURL('image/png');
        } else {
            data = canvas.toDataURL('image/png');
        }
        if (!data) {
            if (!confirm('Assinatura vazia. Deseja salvar em branco?')) return;
        }
        try{ localStorage.setItem('signature_temp', data); } catch(e){ console.error(e); }
        // go back to previous page where importer/editor will pick up the value
        history.back();
    });

    // initial layout
    resizeCanvasForLandscape();
    // initialize signature pad immediately (non-fullscreen) for convenience
    initSignaturePad();
})();
</script>

<?php
$contentHtml = ob_get_clean();
$tempFile = __DIR__ . '/../../../temp_assinatura_' . uniqid() . '.php';
file_put_contents($tempFile, $contentHtml);
$contentFile = $tempFile;
include __DIR__ . '/../layouts/app-wrapper.php';
unlink($tempFile);
?>
