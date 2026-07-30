/**
 * checkin-result.js — Check-in ve biniş kartı ekranı (/check-in)
 *
 * Rezervasyonun tamamını uçuş bacağı bazında gösterir. Check-in her bacak için
 * ayrı açılır (gidiş-dönüşte dönüşün penceresi günler sonra), bu yüzden
 * işlem bacak seviyesinde yapılır ve tüm yolcular birlikte check-in edilir.
 *
 * Rezervasyon iptali burada DEĞİL, /bilet-yonetimi sayfasında yapılır:
 * check-in "uçuşa hazırlan" ekranı, iptal ise rezervasyon yönetimi işlemidir.
 *
 * Tüm dinamik değerler esc() ile kaçırılır: yolcu adı ve sunucu hata mesajları
 * kullanıcı girdisi kaynaklıdır, doğrudan innerHTML'e yazılamaz.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    DHNavGuard.init();

    var view = document.getElementById('checkin-view');
    var params = new URLSearchParams(window.location.search);
    var pnr = params.get('pnr') || '';
    var lastName = params.get('last_name') || '';

    var PAX_LABELS = {
        adult: 'Yetişkin', child: 'Çocuk', infant: 'Bebek', student: 'Öğrenci'
    };
    var CABIN_LABELS = {
        economy: 'Economy', premium_economy: 'Premium Economy', business: 'Business'
    };

    if (!pnr || !lastName) {
        renderError('Bilet bilgileri eksik. Lütfen ana sayfadan tekrar deneyin.');
        return;
    }

    loadReservation();

    // ================= VERİ =================

    function loadReservation() {
        var url = '/api/tickets/manage?pnr=' + encodeURIComponent(pnr) +
            '&last_name=' + encodeURIComponent(lastName);

        fetch(url)
            .then(readJson)
            .then(function (result) {
                if (!result.ok) {
                    renderError(result.data.error || 'Rezervasyon bulunamadı.');
                    return;
                }
                render(result.data);
            })
            .catch(function () {
                renderError('Rezervasyon bilgileri alınamadı. Lütfen tekrar deneyin.');
            });
    }

    function checkInLeg(flightId, btn) {
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'İşleniyor...';
        }

        fetch('/api/checkin', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pnr: pnr, last_name: lastName, flight_id: flightId })
        })
            .then(readJson)
            .then(function (result) {
                if (!result.ok) {
                    renderError(result.data.error || 'Check-in işlemi tamamlanamadı.');
                    return;
                }
                render(result.data);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(function () {
                renderError('Check-in işlemi sırasında bir hata oluştu.');
            });
    }

    function readJson(res) {
        return res.json().then(function (data) {
            return { ok: res.ok, data: data };
        });
    }

    // ================= GÖRÜNÜM =================

    function render(data) {
        if (data.status === 'cancelled') {
            renderError('Bu rezervasyon iptal edilmiş. Check-in yapılamaz.');
            return;
        }

        var anyCheckedIn = data.legs.some(function (leg) { return leg.all_checked_in; });
        if (anyCheckedIn) {
            DHNavGuard.release();
        }

        var html =
            '<div class="dh-checkin-card">' +
            '<div class="dh-checkin-card-header">' +
            '<span class="dh-checkin-badge">Rezervasyon bulundu</span>' +
            '<span class="dh-checkin-pnr">' + esc(data.pnr) + '</span>' +
            '</div>' +
            '<div class="dh-detail-row">' +
            '<span class="dh-detail-label">Yolcu sayısı</span>' +
            '<span class="dh-detail-value">' + countPassengers(data) + ' kişi</span>' +
            '</div>' +
            '</div>';

        data.legs.forEach(function (leg) {
            html += leg.all_checked_in ? renderBoardingPasses(leg) : renderLegCard(leg);
        });

        html +=
            '<div class="dh-checkin-actions">' +
            '<a href="/" class="dh-checkin-back">Ana sayfaya dön</a>' +
            (anyCheckedIn
                ? '<button type="button" class="dh-btn-primary" id="print-btn">Yazdır <i class="ti ti-printer" aria-hidden="true"></i></button>'
                : '') +
            '</div>';

        view.innerHTML = html;

        var printBtn = document.getElementById('print-btn');
        if (printBtn) {
            printBtn.addEventListener('click', function () { window.print(); });
        }

        // Olay dinleyicileri innerHTML sonrası bağlanır (inline onclick yok)
        view.querySelectorAll('[data-checkin-flight]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                checkInLeg(parseInt(btn.dataset.checkinFlight, 10), btn);
            });
        });
    }

    /** Check-in yapılmamış bacak: uçuş özeti + yolcu listesi + eylem düğmesi. */
    function renderLegCard(leg) {
        var canCheckIn = leg.check_in_open === true;

        var html =
            '<div class="dh-checkin-card">' +
            '<div class="dh-checkin-card-header">' +
            '<span class="dh-checkin-badge' + (canCheckIn ? '' : ' dh-checkin-badge-wait') + '">' +
            esc(leg.flight_number) +
            '</span>' +
            '<span class="dh-checkin-pnr">' + esc(leg.origin.iata_code) + ' → ' + esc(leg.destination.iata_code) + '</span>' +
            '</div>';

        if (!canCheckIn) {
            var opensAt = leg.check_in_opens_at ? new Date(leg.check_in_opens_at) : null;
            html +=
                '<div class="dh-checkin-countdown">' +
                '<i class="ti ti-clock-hour-4" aria-hidden="true"></i>' +
                '<div>' +
                '<span class="dh-countdown-label">Check-in açılış zamanı</span>' +
                '<span class="dh-countdown-value">' + (opensAt ? formatDateTime(opensAt) : '—') + '</span>' +
                '<span class="dh-countdown-hint">' + remainingText(opensAt) + '</span>' +
                '</div>' +
                '</div>';
        }

        html +=
            '<div class="dh-checkin-details">' +
            detailRow('Kalkış', formatDate(leg.departure_time) + ' · ' + formatTime(leg.departure_time)) +
            detailRow('Varış', formatTime(leg.arrival_time)) +
            detailRow('Uçak · Kabin', esc(leg.aircraft) + ' · ' + label(CABIN_LABELS, leg.cabin_class)) +
            '</div>' +
            '<div class="dh-ticket-list">';

        leg.tickets.forEach(function (t) {
            html +=
                '<div class="dh-ticket-row">' +
                '<div class="dh-ticket-passenger">' +
                '<strong>' + esc(t.passenger_name) + '</strong>' +
                '<span>' + label(PAX_LABELS, t.passenger_type) + '</span>' +
                '</div>' +
                '<div class="dh-ticket-seat">' +
                (t.seat_number
                    ? '<span class="dh-seat-label">Koltuk</span><span class="dh-seat-value">' + esc(t.seat_number) + '</span>'
                    : '<span class="dh-seat-none">Kucakta</span>') +
                '</div>' +
                '</div>';
        });

        html +=
            '</div>' +
            '<div class="dh-checkin-actions">' +
            '<span class="dh-checkin-back">' + leg.tickets.length + ' yolcu birlikte check-in edilir</span>' +
            '<button type="button" class="dh-btn-primary" data-checkin-flight="' + leg.flight_id + '"' +
            (canCheckIn ? '' : ' disabled') + '>' +
            'Check-in yap <i class="ti ti-arrow-right" aria-hidden="true"></i>' +
            '</button>' +
            '</div>' +
            '</div>';

        return html;
    }

    /** Check-in yapılmış bacak: her yolcu için ayrı biniş kartı. */
    function renderBoardingPasses(leg) {
        var html = '';

        leg.tickets.forEach(function (t) {
            html +=
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
                '<span class="dh-bp-code">' + esc(leg.origin.iata_code) + '</span>' +
                '<span class="dh-bp-city">' + esc(leg.origin.city || '') + '</span>' +
                '</div>' +
                '<div class="dh-bp-arrow"><i class="ti ti-plane" aria-hidden="true"></i></div>' +
                '<div class="dh-bp-endpoint">' +
                '<span class="dh-bp-code">' + esc(leg.destination.iata_code) + '</span>' +
                '<span class="dh-bp-city">' + esc(leg.destination.city || '') + '</span>' +
                '</div>' +
                '</div>' +

                '<div class="dh-bp-grid">' +
                bpField('Yolcu', esc(t.passenger_name)) +
                bpField('Uçuş', esc(leg.flight_number)) +
                bpField('Tarih', formatDate(leg.departure_time)) +
                bpField('Kalkış', formatTime(leg.departure_time)) +
                bpField('Kabin', label(CABIN_LABELS, leg.cabin_class)) +
                bpField('Koltuk', t.seat_number ? esc(t.seat_number) : 'Kucakta', true) +
                '</div>' +
                '</div>' +

                '<div class="dh-bp-stub">' +
                '<div class="dh-bp-stub-seat">' +
                '<span class="dh-bp-stub-label">Koltuk</span>' +
                '<span class="dh-bp-stub-value">' + (t.seat_number ? esc(t.seat_number) : '—') + '</span>' +
                '</div>' +
                '<div class="dh-bp-barcode" aria-hidden="true"></div>' +
                '<span class="dh-bp-stub-pnr">' + esc(pnr) + '</span>' +
                '</div>' +
                '</div>';
        });

        html +=
            '<div class="dh-checkin-note">' +
            '<i class="ti ti-circle-check" aria-hidden="true"></i>' +
            '<span>' + esc(leg.flight_number) + ' uçuşu için check-in tamamlandı. ' +
            'Biniş kartlarınızı uçuştan önce hazır bulundurunuz.</span>' +
            '</div>';

        return html;
    }

    // ================= YARDIMCILAR =================

    /** HTML kaçırma — yolcu adı ve sunucu mesajları kullanıcı girdisi kaynaklı. */
    function esc(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function label(map, key) {
        return map[key] || esc(key || '—');
    }

    function countPassengers(data) {
        if (!data.legs.length) return 0;
        return data.legs[0].tickets.length;
    }

    function detailRow(labelText, value) {
        return '<div class="dh-detail-row">' +
            '<span class="dh-detail-label">' + labelText + '</span>' +
            '<span class="dh-detail-value">' + value + '</span>' +
            '</div>';
    }

    function bpField(labelText, value, highlight) {
        return '<div class="dh-bp-field' + (highlight ? ' dh-bp-field-highlight' : '') + '">' +
            '<span class="dh-bp-field-label">' + labelText + '</span>' +
            '<span class="dh-bp-field-value">' + value + '</span>' +
            '</div>';
    }

    function formatDate(iso) {
        if (!iso) return '—';
        return new Date(iso).toLocaleDateString('tr-TR', {
            day: 'numeric', month: 'long', year: 'numeric', weekday: 'long'
        });
    }

    function formatTime(iso) {
        if (!iso) return '—';
        var d = new Date(iso);
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function formatDateTime(d) {
        return d.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', weekday: 'long' }) +
            ' · ' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function remainingText(opensAt) {
        if (!opensAt) return '';
        var diff = opensAt - new Date();
        if (diff <= 0) return '';

        var total = Math.floor(diff / 60000);
        var days = Math.floor(total / 1440);
        var hours = Math.floor((total % 1440) / 60);
        var minutes = total % 60;

        if (days > 0) return days + ' gün ' + hours + ' saat kaldı';
        if (hours > 0) return hours + ' saat ' + minutes + ' dakika kaldı';
        return minutes + ' dakika kaldı';
    }

    function renderError(message) {
        view.innerHTML =
            '<div class="dh-checkin-card dh-checkin-card-error">' +
            '<i class="ti ti-alert-circle dh-checkin-error-icon" aria-hidden="true"></i>' +
            '<p class="dh-checkin-error-text">' + esc(message) + '</p>' +
            '<a href="/" class="dh-btn-primary dh-checkin-error-btn">Ana sayfaya dön</a>' +
            '</div>';
    }
});
