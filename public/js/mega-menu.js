/**
 * mega-menu.js — Header üst menüsü.
 *
 * Panel yalnızca tıklamayla açılır. Üzerine gelme davranışı CSS'te,
 * yalnızca görsel geri bildirim olarak duruyor.
 *
 * JS panelin genişliğini HESAPLAMAZ — yalnızca üç kenar değerini ölçer:
 * panelin uzayabileceği en sol nokta, sağ kenarı ve varsayılan açıklığı.
 * Genişliği CSS'te içerik belirlediği için sütun sayısı değiştiğinde
 * bu dosyada hiçbir şey güncellenmez.
 */
(function () {
    var header = document.querySelector('.dh-header');
    if (!header) return;

    var nav = header.querySelector('.dh-main-nav');
    var loginBtn = header.querySelector('.dh-login-btn');
    var triggers = Array.prototype.slice.call(header.querySelectorAll('.dh-nav-trigger'));
    var panels = Array.prototype.slice.call(header.querySelectorAll('[data-menu-panel]'));
    if (!triggers.length || !panels.length) return;

    var LEFT_OVERHANG = 48;   // varsayılan açıklık menünün bu kadar solundan başlar
    var MIN_LEFT = 24;        // panelin uzayabileceği en sol nokta

    var openId = null;

    function measure() {
        if (!nav) return;

        var headerRect = header.getBoundingClientRect();
        var navRect = nav.getBoundingClientRect();

        // Sağ kenar "Giriş yap" butonunun sol tarafında bitiyor.
        var rightEdge = loginBtn ? loginBtn.getBoundingClientRect().left : navRect.right;
        var right = Math.round(headerRect.right - rightEdge);

        var defaultLeft = navRect.left - LEFT_OVERHANG;

        header.style.setProperty('--dh-mega-left', MIN_LEFT + 'px');
        header.style.setProperty('--dh-mega-right', Math.max(right, 0) + 'px');
        header.style.setProperty('--dh-mega-min', Math.max(rightEdge - defaultLeft, 0) + 'px');
    }

    function open(id) {
        openId = id;

        triggers.forEach(function (t) {
            t.setAttribute('aria-expanded', t.dataset.menu === id ? 'true' : 'false');
        });
        panels.forEach(function (p) {
            p.hidden = p.dataset.menuPanel !== id;
        });
    }

    function close() {
        openId = null;

        triggers.forEach(function (t) { t.setAttribute('aria-expanded', 'false'); });
        panels.forEach(function (p) { p.hidden = true; });
    }

    triggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            var id = trigger.dataset.menu;

            if (openId === id) { close(); } else { open(id); }
        });
    });

    panels.forEach(function (panel) {
        // Yardım maddeleri katman açıyor, menü arkada açık kalmamalı
        panel.addEventListener('click', function (event) {
            if (event.target.closest('.dh-mega-link')) close();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || !openId) return;

        var active = null;
        triggers.forEach(function (t) {
            if (t.dataset.menu === openId) active = t;
        });

        close();
        if (active) active.focus();
    });

    document.addEventListener('click', function (event) {
        if (openId && !header.contains(event.target)) close();
    });

    document.addEventListener('focusin', function (event) {
        if (openId && !header.contains(event.target)) close();
    });

    window.addEventListener('resize', measure);

    measure();
})();
