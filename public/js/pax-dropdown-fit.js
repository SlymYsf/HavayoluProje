/**
 * pax-dropdown-fit.js — Yolcu/kabin panelinin ekran dışına taşmasını önler.
 *
 * Panelin açılıp kapanmasını YÖNETMEZ (o iş search-form.js'te). Sadece panelin
 * görünürlüğünü izler ve panel açıldığında:
 *   1. Altta kalan boşluğa göre max-height hesaplar (--dh-pax-max)
 *   2. Altta yer yoksa ve üstte daha çok yer varsa paneli yukarı açar
 *
 * search-form.js'ten önce veya sonra yüklenmesi fark etmez — bağımsız çalışır.
 */
(function () {
    'use strict';

    var GAP = 16;        // panel ile ekran kenarı arasında bırakılacak boşluk
    var MAX = 440;       // içeriğin doğal yüksekliği, fazlasına gerek yok
    var MIN_BELOW = 260; // altta bundan az yer kalırsa yukarı açmayı dene
    var MIN_HEIGHT = 200;

    function fit(panel, trigger) {
        var rect = trigger.getBoundingClientRect();
        var below = window.innerHeight - rect.bottom - GAP;
        var above = rect.top - GAP;

        var up = below < MIN_BELOW && above > below;
        var space = Math.max(MIN_HEIGHT, Math.min(MAX, up ? above : below));
        var value = Math.round(space) + 'px';

        // Idempotent yazım: değer değişmediyse DOM'a hiç dokunma.
        // Aksi halde MutationObserver kendi yazdığımız değişikliği yakalar ve döngüye gireriz.
        if (panel.classList.contains('dh-drop-up') !== up) {
            panel.classList.toggle('dh-drop-up', up);
        }
        if (panel.style.getPropertyValue('--dh-pax-max') !== value) {
            panel.style.setProperty('--dh-pax-max', value);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('passenger-dropdown');
        var trigger = document.getElementById('passenger-toggle');
        if (!panel || !trigger) return;

        var apply = function () {
            // offsetParent === null → gizli. hidden / display / class, hangisiyle
            // gizlenirse gizlensin doğru sonuç verir.
            if (panel.offsetParent !== null) fit(panel, trigger);
        };

        new MutationObserver(apply).observe(panel, {
            attributes: true,
            attributeFilter: ['hidden', 'style', 'class']
        });

        window.addEventListener('resize', apply);
        window.addEventListener('scroll', apply, { passive: true });

        apply();
    });
})();
