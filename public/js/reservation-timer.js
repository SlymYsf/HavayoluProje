document.addEventListener('DOMContentLoaded', function () {
    var bar = document.getElementById('reservation-timer');
    if (!bar) return;

    var valueEl = document.getElementById('timer-value');
    var expiresAt = new Date(bar.dataset.expires);
    var expired = false;
    var intervalId = null;

    var WARNING_SECONDS = 120; // Son 2 dakikada uyarı rengine geç

    function remainingSeconds() {
        return Math.floor((expiresAt - new Date()) / 1000);
    }

    function tick() {
        var left = remainingSeconds();

        if (left <= 0) {
            handleExpiry();
            return;
        }

        var minutes = Math.floor(left / 60);
        var seconds = left % 60;
        valueEl.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

        bar.classList.toggle('dh-timer-warning', left <= WARNING_SECONDS);
    }

    function handleExpiry() {
        if (expired) return;
        expired = true;
        clearInterval(intervalId);

        valueEl.textContent = '00:00';
        bar.classList.add('dh-timer-warning');

        // Geri dönüş uyarısı bu senaryoda gösterilmemeli
        if (window.DHNavGuard && typeof DHNavGuard.release === 'function') {
            DHNavGuard.release();
        }

        showExpiryDialog();
    }

    function showExpiryDialog() {
        var overlay = document.createElement('div');
        overlay.className = 'dh-confirm-overlay';
        overlay.innerHTML =
            '<div class="dh-confirm-backdrop"></div>' +
            '<div class="dh-confirm-box" role="alertdialog" aria-modal="true">' +
            '<i class="ti ti-clock-x dh-confirm-icon" aria-hidden="true"></i>' +
            '<p class="dh-confirm-text">Rezervasyon süreniz doldu.<br>Güvenliğiniz için işlem sonlandırıldı, lütfen aramanızı yeniden yapın.</p>' +
            '<div class="dh-confirm-actions">' +
            '<button type="button" class="dh-btn-primary dh-confirm-yes">Ana sayfaya dön</button>' +
            '</div>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        overlay.querySelector('.dh-confirm-yes').addEventListener('click', goHome);

        // Kullanıcı diyalogu görmezse 10 saniye sonra kendiliğinden yönlendir
        setTimeout(goHome, 10000);
    }

    function goHome() {
        // Doğrudan '/' yerine sunucu rotasına gidiyoruz: session orada temizleniyor
        // ve ana sayfa "süreniz doldu" bildirimiyle açılıyor.
        window.location.replace('/rezervasyon/zaman-asimi');
    }

    // Sekme arka plandayken tarayıcı setInterval'i yavaşlatır;
    // geri dönüldüğünde süre gerçekten dolmuşsa hemen yakalanır.
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) tick();
    });

    tick();
    intervalId = setInterval(tick, 1000);
});
