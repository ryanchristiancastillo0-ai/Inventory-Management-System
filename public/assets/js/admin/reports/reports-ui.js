/* =========================================================
   ADMIN REPORTS — toast
   Page-owned window.showToast(), used by the table script and
   the inline flash component.
   ========================================================= */
(function () {
    var stack = document.getElementById('toastStack');

    window.showToast = function (message, icon, isError) {
        if (!stack) return;
        var t = document.createElement('div');
        t.className = 'toast-app' + (isError ? ' is-error' : '');
        t.innerHTML = '<i class="bi ' + (icon || (isError ? 'bi-exclamation-circle' : 'bi-check2-circle')) + '" aria-hidden="true"></i><span></span>';
        t.querySelector('span').textContent = message;
        stack.appendChild(t);
        setTimeout(function () {
            t.classList.add('toast-out');
            t.addEventListener('animationend', function () { t.remove(); }, { once: true });
        }, 3600);
    };
})();
