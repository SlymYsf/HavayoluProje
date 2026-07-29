document.addEventListener('DOMContentLoaded', function () {
    var stripDays = document.getElementById('strip-days');
    var stripRoute = document.getElementById('strip-route');
    var prevBtn = document.getElementById('strip-prev');
    var nextBtn = document.getElementById('strip-next');

    if (!stripDays) return;

    var params = new URLSearchParams(window.location.search);
    var originIds = (params.get('origin') || '').split(',').filter(Boolean);
    var destinationIds = (params.get('destination') || '').split(',').filter(Boolean);
    var originLabel = params.get('origin_label') || '';
    var destinationLabel = params.get('destination_label') || '';
    var currentDate = params.get('date') || '';

    stripRoute.textContent = originLabel + ' → ' + destinationLabel;

    if (!originIds.length || !destinationIds.length || !currentDate) {
        stripDays.innerHTML = '<div class="dh-strip-empty">Arama bilgileri eksik.</div>';
        return;
    }

    var centerDate = currentDate;

    function loadStrip() {
        stripDays.innerHTML = '<div class="dh-strip-loading">Yükleniyor...</div>';

        var url = '/api/fares/calendar?origin_airport_id=' + originIds[0] +
            '&destination_airport_id=' + destinationIds[0] +
            '&date=' + centerDate + '&range=3';

        fetch(url)
            .then(function (res) { return res.json(); })
            .then(function (data) { renderStrip(data.strip); })
            .catch(function () {
                stripDays.innerHTML = '<div class="dh-strip-empty">Fiyat şeridi yüklenemedi.</div>';
            });
    }

    function renderStrip(strip) {
        stripDays.innerHTML = '';

        strip.forEach(function (item) {
            var day = document.createElement('button');
            day.type = 'button';
            day.className = 'dh-strip-day';
            if (item.date === currentDate) day.classList.add('dh-strip-day-active');
            if (!item.has_flight) day.classList.add('dh-strip-day-disabled');

            var d = new Date(item.date + 'T00:00:00');
            var dayNum = d.getDate();
            var monthShort = d.toLocaleDateString('tr-TR', { month: 'short' });
            var weekday = d.toLocaleDateString('tr-TR', { weekday: 'long' });

            var priceHtml = item.price !== null
                ? '<span class="dh-strip-price">' + Math.round(item.price).toLocaleString('tr-TR') + '₺</span>'
                : '<span class="dh-strip-price dh-strip-price-none">—</span>';

            day.innerHTML =
                '<span class="dh-strip-daynum">' + dayNum + '</span>' +
                '<span class="dh-strip-daymonth">' + monthShort + '</span>' +
                '<span class="dh-strip-dayweek">' + weekday + '</span>' +
                priceHtml;

            if (item.has_flight) {
                day.addEventListener('click', function () {
                    navigateToDate(item.date);
                });
            }

            stripDays.appendChild(day);
        });
    }

    function navigateToDate(newDate) {
        var newParams = new URLSearchParams(window.location.search);
        newParams.set('date', newDate);
        window.location.replace('/ucus-sonuclari?' + newParams.toString());
    }

    function shiftCenter(days) {
        var d = new Date(centerDate + 'T00:00:00');
        d.setDate(d.getDate() + days);
        centerDate = d.toISOString().slice(0, 10);
        loadStrip();
    }

    prevBtn.addEventListener('click', function () { shiftCenter(-7); });
    nextBtn.addEventListener('click', function () { shiftCenter(7); });

    loadStrip();
});
