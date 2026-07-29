/**
 * payment-form.js — Ödeme sayfası kart alanları.
 *
 * Gerçek bir ödeme altyapısı yok; bu maskeleme yalnızca kullanıcı deneyimi
 * içindir. Kart bilgileri hiçbir yere kaydedilmiyor.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var number = document.getElementById('card-number');
    var expiry = document.getElementById('card-expiry');
    var cvv    = document.getElementById('card-cvv');
    var form   = document.getElementById('payment-form');
    var payBtn = document.getElementById('pay-btn');

    if (number) {
        number.addEventListener('input', function () {
            var d = number.value.replace(/\D/g, '').slice(0, 16);
            number.value = d.replace(/(.{4})/g, '$1 ').trim();
        });
    }

    if (expiry) {
        expiry.addEventListener('input', function () {
            var d = expiry.value.replace(/\D/g, '').slice(0, 4);
            expiry.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
        });
    }

    if (cvv) {
        cvv.addEventListener('input', function () {
            cvv.value = cvv.value.replace(/\D/g, '').slice(0, 4);
        });
    }

    // Çift tıklamada iki rezervasyon oluşmasın
    if (form && payBtn) {
        form.addEventListener('submit', function () {
            payBtn.disabled = true;
            payBtn.textContent = 'İşleniyor...';
        });
    }
});
