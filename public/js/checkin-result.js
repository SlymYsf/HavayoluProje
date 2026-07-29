document.addEventListener('DOMContentLoaded', function () {
    DHNavGuard.init();

    var view = document.getElementById('checkin-view');
    var params = new URLSearchParams(window.location.search);
    var pnr = params.get('pnr') || '';
    var lastName = params.get('last_name') || '';

    if (!pnr || !lastName) {
        renderError('Bilet bilgileri eksik. Lütfen ana sayfadan tekrar deneyin.');
        return;
    }

    fetchTicket();

    function fetchTicket() {
        var url = '/api/tickets/manage?pnr=' + encodeURIComponent(pnr) + '&last_name=' + encodeURIComponent(lastName);

        fetch(url)
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .catch(function () {
                renderError('Bilet bilgileri alınamadı. Lütfen tekrar deneyin.');
                return null;
            })
            .then(function (result) {
                if (!result) return;

                if (!result.ok) {
                    renderError(result.data.error || 'Bilet bulunamadı.');
                    return;
                }

                var ticket = result.data;

                if (ticket.status === 'cancelled') {
                    renderError('Bu bilet iptal edilmiş. Check-in yapılamaz.');
                    return;
                }

                if (ticket.checked_in_at) {
                    renderBoardingPass(ticket);
                } else if (ticket.check_in_open === false) {
                    renderNotYetOpen(ticket);
                } else {
                    renderSummary(ticket);
                }
            });
    }

    function performCheckIn(ticket) {
        var btn = document.getElementById('do-checkin-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'İşleniyor...';
        }

        fetch('/api/checkin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pnr: pnr, last_name: lastName })
        })
            .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
            .then(function (result) {
                if (!result.ok) {
                    renderError(result.data.error || 'Check-in işlemi tamamlanamadı.');
                    return;
                }
                // Check-in yanıtındaki güncel alanları mevcut bilete işle
                ticket.checked_in_at = result.data.checked_in_at;
                ticket.seat_number = result.data.seat_number;
                renderBoardingPass(ticket);
            })
            .catch(function () {
                renderError('Check-in işlemi sırasında bir hata oluştu.');
            });
    }

    function renderSummary(ticket) {
        var flight = ticket.flight || {};
        var route = flight.route || {};

        view.innerHTML =
            '<div class="dh-checkin-card">' +
            '<div class="dh-checkin-card-header">' +
            '<span class="dh-checkin-badge">Bilet bulundu</span>' +
            '<span class="dh-checkin-pnr">' + ticket.pnr + '</span>' +
            '</div>' +
            buildTicketDetails(ticket, flight, route) +
            '<div class="dh-checkin-actions">' +
            '<a href="/" class="dh-checkin-back">Vazgeç</a>' +
            '<button type="button" class="dh-btn-primary" id="do-checkin-btn">Check-in yap <i class="ti ti-arrow-right" aria-hidden="true"></i></button>' +
            '</div>' +
            '</div>';

        document.getElementById('do-checkin-btn').addEventListener('click', function () {
            performCheckIn(ticket);
        });
    }

    function renderNotYetOpen(ticket) {
        var flight = ticket.flight || {};
        var route = flight.route || {};
        var opensAt = ticket.check_in_opens_at ? new Date(ticket.check_in_opens_at) : null;

        view.innerHTML =
            '<div class="dh-checkin-card">' +
            '<div class="dh-checkin-card-header">' +
            '<span class="dh-checkin-badge dh-checkin-badge-wait">Check-in henüz açılmadı</span>' +
            '<span class="dh-checkin-pnr">' + ticket.pnr + '</span>' +
            '</div>' +
            '<div class="dh-checkin-countdown">' +
            '<i class="ti ti-clock-hour-4" aria-hidden="true"></i>' +
            '<div>' +
            '<span class="dh-countdown-label">Check-in açılış zamanı</span>' +
            '<span class="dh-countdown-value">' + (opensAt ? formatDateTime(opensAt) : '—') + '</span>' +
            '<span class="dh-countdown-hint">' + remainingText(opensAt) + '</span>' +
            '</div>' +
            '</div>' +
            buildTicketDetails(ticket, flight, route) +
            '<div class="dh-checkin-actions">' +
            '<a href="/" class="dh-checkin-back">Ana sayfaya dön</a>' +
            '<button type="button" class="dh-btn-primary" disabled>Check-in yap</button>' +
            '</div>' +
            '</div>';
    }

    function formatDateTime(d) {
        return d.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', weekday: 'long' }) +
            ' · ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function remainingText(opensAt) {
        if (!opensAt) return '';
        var diffMs = opensAt - new Date();
        if (diffMs <= 0) return '';
        var totalMinutes = Math.floor(diffMs / 60000);
        var days = Math.floor(totalMinutes / 1440);
        var hours = Math.floor((totalMinutes % 1440) / 60);
        var minutes = totalMinutes % 60;

        if (days > 0) return days + ' gün ' + hours + ' saat kaldı';
        if (hours > 0) return hours + ' saat ' + minutes + ' dakika kaldı';
        return minutes + ' dakika kaldı';
    }

    function renderBoardingPass(ticket) {
        DHNavGuard.release();
        var flight = ticket.flight || {};
        var route = flight.route || {};
        var origin = route.origin_airport || {};
        var destination = route.destination_airport || {};
        var passenger = ticket.passenger || {};

        view.innerHTML =
            '<div class="dh-boarding-pass">' +
            '<div class="dh-bp-main">' +
            '<div class="dh-bp-header">' +
            '<div class="dh-bp-brand">' +
            '<i class="ti ti-plane-departure" aria-hidden="true"></i>' +
            '<span>Devlet Havayolları</span>' +
            '</div>' +
            '<span class="dh-bp-label">Biniş Kartı</span>' +
            '</div>' +

            '<div class="dh-bp-route">' +
            '<div class="dh-bp-endpoint">' +
            '<span class="dh-bp-code">' + (origin.iata_code || '—') + '</span>' +
            '<span class="dh-bp-city">' + (origin.city || '') + '</span>' +
            '</div>' +
            '<div class="dh-bp-arrow"><i class="ti ti-plane" aria-hidden="true"></i></div>' +
            '<div class="dh-bp-endpoint">' +
            '<span class="dh-bp-code">' + (destination.iata_code || '—') + '</span>' +
            '<span class="dh-bp-city">' + (destination.city || '') + '</span>' +
            '</div>' +
            '</div>' +

            '<div class="dh-bp-grid">' +
            bpField('Yolcu', passengerName(passenger)) +
            bpField('Uçuş', flight.flight_number || '—') +
            bpField('Tarih', formatDate(flight.departure_time)) +
            bpField('Kalkış', formatTime(flight.departure_time)) +
            bpField('Kabin', cabinLabel(ticket.cabin_class)) +
            bpField('Koltuk', ticket.seat_number || '—', true) +
            '</div>' +
            '</div>' +

            '<div class="dh-bp-stub">' +
            '<div class="dh-bp-stub-seat">' +
            '<span class="dh-bp-stub-label">Koltuk</span>' +
            '<span class="dh-bp-stub-value">' + (ticket.seat_number || '—') + '</span>' +
            '</div>' +
            '<div class="dh-bp-barcode" aria-hidden="true"></div>' +
            '<span class="dh-bp-stub-pnr">' + ticket.pnr + '</span>' +
            '</div>' +
            '</div>' +

            '<div class="dh-checkin-note">' +
            '<i class="ti ti-circle-check" aria-hidden="true"></i>' +
            '<span>Check-in işleminiz tamamlandı. Biniş kartınızı uçuşunuzdan önce hazır bulundurunuz.</span>' +
            '</div>' +

            '<div class="dh-checkin-actions">' +
            '<a href="/" class="dh-checkin-back">Ana sayfaya dön</a>' +
            '<button type="button" class="dh-btn-primary" onclick="window.print()">Yazdır <i class="ti ti-printer" aria-hidden="true"></i></button>' +
            '</div>';
    }

    function buildTicketDetails(ticket, flight, route) {
        var origin = route.origin_airport || {};
        var destination = route.destination_airport || {};
        var passenger = ticket.passenger || {};

        return '<div class="dh-checkin-details">' +
            detailRow('Yolcu', passengerName(passenger)) +
            detailRow('Uçuş numarası', flight.flight_number || '—') +
            detailRow('Rota', (origin.iata_code || '—') + ' → ' + (destination.iata_code || '—')) +
            detailRow('Tarih', formatDate(flight.departure_time)) +
            detailRow('Kalkış saati', formatTime(flight.departure_time)) +
            detailRow('Kabin', cabinLabel(ticket.cabin_class)) +
            detailRow('Koltuk', ticket.seat_number || 'Atanmadı') +
            '</div>';
    }

    function detailRow(label, value) {
        return '<div class="dh-detail-row">' +
            '<span class="dh-detail-label">' + label + '</span>' +
            '<span class="dh-detail-value">' + value + '</span>' +
            '</div>';
    }

    function bpField(label, value, highlight) {
        return '<div class="dh-bp-field' + (highlight ? ' dh-bp-field-highlight' : '') + '">' +
            '<span class="dh-bp-field-label">' + label + '</span>' +
            '<span class="dh-bp-field-value">' + value + '</span>' +
            '</div>';
    }

    function passengerName(passenger) {
        var first = passenger.first_name || passenger.name || '';
        var last = passenger.last_name || passenger.surname || '';
        var full = (first + ' ' + last).trim();
        return full || '—';
    }

    function cabinLabel(cabinClass) {
        if (cabinClass === 'economy') return 'Economy';
        if (cabinClass === 'business') return 'Business';
        if (cabinClass === 'premium_economy') return 'Premium Economy';
        return cabinClass || '—';
    }

    function formatDate(isoString) {
        if (!isoString) return '—';
        var d = new Date(isoString);
        return d.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', year: 'numeric', weekday: 'long' });
    }

    function formatTime(isoString) {
        if (!isoString) return '—';
        var d = new Date(isoString);
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function renderError(message) {
        view.innerHTML =
            '<div class="dh-checkin-card dh-checkin-card-error">' +
            '<i class="ti ti-alert-circle dh-checkin-error-icon" aria-hidden="true"></i>' +
            '<p class="dh-checkin-error-text">' + message + '</p>' +
            '<a href="/" class="dh-btn-primary dh-checkin-error-btn">Ana sayfaya dön</a>' +
            '</div>';
    }
});
