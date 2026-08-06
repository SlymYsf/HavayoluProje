/**
 * yesilkoy-hero.js — Yeşilköy Havalimanı sayfasındaki kaydırmalı hero.
 *
 * Otomatik geçiş 6 saniyede bir; kullanıcı bir butona ya da noktaya
 * tıkladığında zamanlayıcı sıfırlanıyor. Fare üzerinde durunca geçiş
 * duraklıyor — okumaya çalışan kullanıcıya karşı incelik.
 */
(function () {
    var track = document.getElementById('yk-track');
    var prev = document.getElementById('yk-prev');
    var next = document.getElementById('yk-next');
    var dotsBox = document.getElementById('yk-dots');
    if (!track) return;

    var slides = Array.prototype.slice.call(track.children);
    if (slides.length < 2) return;

    var current = 0;
    var timer = null;
    var INTERVAL = 6000;

    // Noktaları oluştur
    slides.forEach(function (_, i) {
        var dot = document.createElement('button');
        dot.type = 'button';
        dot.className = 'dh-yk-dot';
        dot.setAttribute('role', 'tab');
        dot.setAttribute('aria-label', (i + 1) + '. görsel');
        dot.addEventListener('click', function () { go(i); });

        dotsBox.appendChild(dot);
    });

    function render() {
        track.style.transform = 'translateX(-' + (current * 100) + '%)';

        Array.prototype.slice.call(dotsBox.children).forEach(function (dot, i) {
            dot.classList.toggle('dh-yk-dot-active', i === current);
        });
    }

    function go(index) {
        current = (index + slides.length) % slides.length;
        render();
        reset();
    }

    function step() { go(current + 1); }

    function start() { timer = setInterval(step, INTERVAL); }
    function stop() { clearInterval(timer); }
    function reset() { stop(); start(); }

    if (prev) prev.addEventListener('click', function () { go(current - 1); });
    if (next) next.addEventListener('click', function () { go(current + 1); });

    var hero = track.parentElement;
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', start);

    render();
    start();
})();
