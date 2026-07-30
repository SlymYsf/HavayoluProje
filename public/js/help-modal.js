/**
 * help-modal.js — Form altındaki yardım bağlantılarını katman içinde açar.
 *
 * İçerikler partials/help-modals.blade.php içinde gizli olarak durur; burada
 * yalnızca gösterim yönetiliyor. Ayrı sayfaya yönlendirmiyoruz, çünkü kullanıcı
 * form doldururken girdiğini kaybetmemeli.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var store = document.getElementById('help-contents');
    if (!store) return;

    var overlay = null;
    var lastFocused = null;

    document.querySelectorAll('[data-help]').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            open(trigger.dataset.help, trigger);
        });
    });

    function open(id, trigger) {
        var content = store.querySelector('[data-help-id="' + id + '"]');
        if (!content) return;

        close();
        lastFocused = trigger;

        overlay = document.createElement('div');
        overlay.className = 'dh-confirm-overlay';
        overlay.innerHTML =
            '<div class="dh-confirm-backdrop"></div>' +
            '<div class="dh-help-box" role="dialog" aria-modal="true" aria-label="' +
            escapeAttr(content.dataset.helpTitle) + '">' +
            '<div class="dh-help-header">' +
            '<span>' + escapeHtml(content.dataset.helpTitle) + '</span>' +
            '<button type="button" class="dh-help-close" aria-label="Kapat">' +
            '<i class="ti ti-x" aria-hidden="true"></i></button>' +
            '</div>' +
            '<div class="dh-help-body">' + content.innerHTML + '</div>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        overlay.querySelector('.dh-help-close').addEventListener('click', close);
        overlay.querySelector('.dh-confirm-backdrop').addEventListener('click', close);
        overlay.querySelector('.dh-help-close').focus();

        document.addEventListener('keydown', onKeydown);
    }

    function close() {
        if (!overlay) return;

        document.removeEventListener('keydown', onKeydown);
        document.body.removeChild(overlay);
        document.body.style.overflow = '';
        overlay = null;

        // Odağı bağlantıya geri ver: klavye kullanıcısı kaldığı yerden devam etsin
        if (lastFocused) {
            lastFocused.focus();
            lastFocused = null;
        }
    }

    function onKeydown(e) {
        if (e.key === 'Escape') close();
    }

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, '&quot;');
    }
});
