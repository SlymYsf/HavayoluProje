document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('checkin-form');
    var resultBox = document.getElementById('checkin-result');

    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        var pnr = document.getElementById('checkin-pnr').value.trim();
        var lastName = document.getElementById('checkin-lastname').value.trim();

        if (!pnr || !lastName) {
            if (resultBox) {
                resultBox.innerHTML = '<p class="dh-msg">Lütfen PNR ve soyad bilgisini girin.</p>';
            }
            return;
        }

        var params = new URLSearchParams();
        params.set('pnr', pnr);
        params.set('last_name', lastName);

        window.location.href = '/check-in?' + params.toString();
    });
});
