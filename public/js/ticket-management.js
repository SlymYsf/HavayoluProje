/**
 * ticket-management.js — Ana sayfadaki "Bilet yönetimi" sekmesi.
 *
 * Formu doğrular ve kullanıcıyı /bilet-yonetimi sayfasına yönlendirir.
 * Rezervasyon detayları orada manage-result.js tarafından yükleniyor.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var form = document.getElementById('manage-form');
    if (!form) return;

    var pnrInput = document.getElementById('manage-pnr');
    var lastNameInput = document.getElementById('manage-lastname');
    var result = document.getElementById('manage-result');

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var pnr = (pnrInput.value || '').trim().toUpperCase();
        var lastName = (lastNameInput.value || '').trim();

        if (!pnr || !lastName) {
            showError('Rezervasyon kodu ve soyad zorunludur.');
            return;
        }

        window.location.href = '/bilet-yonetimi'
            + '?pnr=' + encodeURIComponent(pnr)
            + '&last_name=' + encodeURIComponent(lastName);
    });

    function showError(message) {
        if (!result) return;

        result.innerHTML =
            '<div class="dh-msg">' +
            '<i class="ti ti-alert-circle" aria-hidden="true"></i> ' +
            message +
            '</div>';
    }
});
