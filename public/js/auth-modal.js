/**
 * auth-modal.js — Giriş katmanı.
 *
 * Üyelik ayrı bir sayfada (/uye-ol); bu dosya yalnızca giriş katmanını
 * açıp kapatıyor. Form henüz bir uç noktaya bağlı olmadığı için gönderim
 * yerine bilgilendirme mesajı gösteriliyor.
 */
(function () {
    var overlay = document.getElementById('auth-modal');
    var closeBtn = document.getElementById('auth-close');
    var notice = document.getElementById('auth-notice');
    if (!overlay) return;

    var triggers = document.querySelectorAll('[data-open-auth]');
    var lastFocused = null;

    function open() {
        lastFocused = document.activeElement;

        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        if (notice) notice.hidden = true;

        var first = overlay.querySelector('input');
        if (first) first.focus();
    }

    function close() {
        overlay.hidden = true;
        document.body.style.overflow = '';

        if (lastFocused) lastFocused.focus();
    }

    triggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            open();
        });
    });

    overlay.querySelectorAll('.dh-auth-submit, .dh-auth-link').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!notice) return;

            notice.hidden = false;
            notice.scrollIntoView({ block: 'nearest' });
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', close);

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) close();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !overlay.hidden) close();
    });
})();
