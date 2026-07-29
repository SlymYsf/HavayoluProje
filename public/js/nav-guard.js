window.DHNavGuard = (function () {
    var active = false;
    var overlay = null;

    function init() {
        if (active) return;
        active = true;

        history.pushState({ dhGuard: true }, '', location.href);
        window.addEventListener('popstate', onPopState);
    }

    /** İşlem tamamlandığında koruma kaldırılır (örn. check-in başarılı). */
    function release() {
        active = false;
        window.removeEventListener('popstate', onPopState);
    }

    function onPopState() {
        if (!active) return;
        history.pushState({ dhGuard: true }, '', location.href);
        showDialog();
    }

    function showDialog() {
        if (overlay) return;

        overlay = document.createElement('div');
        overlay.className = 'dh-confirm-overlay';
        overlay.innerHTML =
            '<div class="dh-confirm-backdrop"></div>' +
            '<div class="dh-confirm-box" role="alertdialog" aria-modal="true">' +
            '<i class="ti ti-alert-triangle dh-confirm-icon" aria-hidden="true"></i>' +
            '<p class="dh-confirm-text">Geri dönerseniz işleminiz iptal olur. Yine de geri dönmek istiyor musunuz?</p>' +
            '<div class="dh-confirm-actions">' +
            '<button type="button" class="dh-confirm-cancel">İptal</button>' +
            '<button type="button" class="dh-btn-primary dh-confirm-yes">Evet, geri dön</button>' +
            '</div>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        overlay.querySelector('.dh-confirm-yes').addEventListener('click', function () {
            release();
            window.location.replace('/');
        });

        overlay.querySelector('.dh-confirm-cancel').addEventListener('click', closeDialog);
        overlay.querySelector('.dh-confirm-backdrop').addEventListener('click', closeDialog);
        document.addEventListener('keydown', onEscape);
    }

    function closeDialog() {
        if (!overlay) return;
        overlay.remove();
        overlay = null;
        document.body.style.overflow = '';
        document.removeEventListener('keydown', onEscape);
    }

    function onEscape(e) {
        if (e.key === 'Escape') closeDialog();
    }

    return { init: init, release: release };
})();
