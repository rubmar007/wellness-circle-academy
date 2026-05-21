'use strict';
/* Único JS del proyecto. Aislado, vanilla, sin dependencias.
   Función: copiar al portapapeles el contenido del elemento apuntado por
   data-copy-target del botón, y mostrar feedback temporal. */
(function () {
    document.querySelectorAll('button[data-copy-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var sel = btn.getAttribute('data-copy-target');
            var t = sel ? document.querySelector(sel) : null;
            if (!t) { return; }
            var text = ('value' in t) ? t.value : t.textContent;
            var original = btn.getAttribute('data-copy-label-original') || btn.textContent;
            var done = btn.getAttribute('data-copy-label-done') || 'Copiado';
            var ok = function () {
                btn.textContent = done;
                btn.setAttribute('data-state', 'done');
                setTimeout(function () { btn.textContent = original; btn.removeAttribute('data-state'); }, 2000);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(ok).catch(function () { fallback(t); ok(); });
            } else { fallback(t); ok(); }
        });
    });
    function fallback(t) {
        try { t.focus(); if (t.select) { t.select(); } document.execCommand('copy'); }
        catch (e) { /* sin clipboard disponible; el usuario puede copiar manualmente */ }
    }
})();
