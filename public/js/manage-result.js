/**
 * manage-result.js — Bilet yönetimi sayfası (/bilet-yonetimi)
 *
 * Rezervasyonun tamamını uçuş bacağı bazında gösterir ve iptal işlemini
 * yürütür. Check-in ekranıyla aynı API'yi kullanıyor; fark amaçta:
 * burada rezervasyon yönetiliyor, orada uçuşa hazırlanılıyor.
 *
 * Tüm dinamik değerler esc() ile kaçırılır: yolcu adı ve sunucu hata
 * mesajları kullanıcı girdisi kaynaklıdır.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var view = document.getElementById('manage-view');
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
        renderError('Rezervasyon bilgileri eksik. Lütfen ana sayfadan tekrar deneyin.');
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

    function readJson(res) {
        return res.json().then(function (data) {
            return { ok: res.ok, data: data };
        });
    }

    // ================= GÖRÜNÜM =================

    function render(data) {
        var cancelled = data.status === 'cancelled';

        // Kalkışı geçmiş uçuş iptal edilemez
        var upcoming = data.legs.some(function (leg) {
            return new Date(leg.departure_time) > new Date();
        });

        var html =
            '<div class="dh-checkin-card">' +
            '<div class="dh-checkin-card-header">' +
            '<span class="dh-checkin-badge' + (cancelled ? ' dh-checkin-badge-wait' : '') + '">' +
            (cancelled ? 'İptal edilmiş' : 'Rezervasyon bulundu') +
            '</span>' +
            '<span class="dh-checkin-pnr">' + esc(data.pnr) + '</span>' +
            '</div>' +
            '<div class="dh-checkin-details">' +
            detailRow('Yolcu sayısı', countPassengers(data) + ' kişi') +
            detailRow('Uçuş sayısı', data.legs.length) +
            '</div>' +
            '</div>';

        data.legs.forEach(function (leg) {
            html += renderLeg(leg, cancelled);
        });

        html +=
            '<div class="dh-checkin-card">' +
            '<div class="dh-summary-total">' +
            '<span>' + (cancelled ? 'İade edilecek tutar' : 'Toplam tutar') + '</span>' +
            '<span class="dh-summary-total-value">' + formatPrice(data.total || 0) + '</span>' +
            '</div>' +
            '</div>';

        if (cancelled) {
            html +=
                '<div class="dh-checkin-note">' +
                '<i class="ti ti-info-circle" aria-hidden="true"></i>' +
                '<span>Bu rezervasyon iptal edilmiştir. İade, ödemenin yapıldığı karta ' +
                '3-5 iş günü içinde yansıtılır.</span>' +
                '</div>';
        }

        html +=
            '<div class="dh-checkin-actions">' +
            (!cancelled && upcoming
                ? '<button type="button" class="dh-btn-primary dh-cancel-btn" id="cancel-btn">' +
                '<i class="ti ti-x" aria-hidden="true"></i> Rezervasyonu iptal et</button>'
                : '<span></span>') +
            (!cancelled
                ? '<a href="' + checkInUrl() + '" class="dh-btn-primary dh-checkin-error-btn">' +
                'Check-in\'e git <i class="ti ti-arrow-right" aria-hidden="true"></i></a>'
                : '') +
            '</div>';

        view.innerHTML = html;

        var cancelBtn = document.getElementById('cancel-btn');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', function () {
                confirmCancel(data);
            });
        }
    }

    function renderLeg(leg, cancelled) {
        var html =
            '<div class="dh-checkin-card">' +
            '<div class="dh-checkin-card-header">' +
            '<span class="dh-checkin-badge' + (cancelled ? ' dh-checkin-badge-wait' : '') + '">' +
            esc(leg.flight_number) +
            '</span>' +
            '<span class="dh-checkin-pnr">' +
            esc(leg.origin.iata_code) + ' → ' + esc(leg.destination.iata_code) +
            '</span>' +
            '</div>' +
            '<div class="dh-checkin-details">' +
            detailRow('Güzergah', esc(leg.origin.city || '') + ' → ' + esc(leg.destination.city || '')) +
            detailRow('Kalkış', formatDate(leg.departure_time) + ' · ' + formatTime(leg.departure_time)) +
            detailRow('Varış', formatTime(leg.arrival_time)) +
            detailRow('Uçak · Kabin', esc(leg.aircraft) + ' · ' + label(CABIN_LABELS, leg.cabin_class)) +
            detailRow('Check-in', checkInStatus(leg, cancelled)) +
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
                    ? '<span class="dh-seat-label">Koltuk</span>' +
                    '<span class="dh-seat-value">' + esc(t.seat_number) + '</span>'
                    : '<span class="dh-seat-none">Kucakta</span>') +
                '</div>' +
                '<div class="dh-ticket-price">' + formatPrice(t.final_price) + '</div>' +
                '</div>';
        });

        return html + '</div></div>';
    }

    function checkInStatus(leg, cancelled) {
        if (cancelled) return 'Geçersiz';
        if (leg.all_checked_in) return 'Tamamlandı';
        if (leg.check_in_open) return 'Açık';

        return leg.check_in_opens_at
            ? formatDate(leg.check_in_opens_at) + ' · ' + formatTime(leg.check_in_opens_at) + ' tarihinde açılır'
            : 'Henüz açılmadı';
    }

    // ================= İPTAL =================

    /** İptal geri alınamaz; onay diyaloğu gösteriliyor. */
    function confirmCancel(data) {
        var overlay = document.createElement('div');
        overlay.className = 'dh-confirm-overlay';
        overlay.innerHTML =
            '<div class="dh-confirm-backdrop"></div>' +
            '<div class="dh-confirm-box" role="alertdialog" aria-modal="true">' +
            '<i class="ti ti-alert-triangle dh-confirm-icon" aria-hidden="true"></i>' +
            '<p class="dh-confirm-text">' +
            '<strong>' + esc(data.pnr) + '</strong> kodlu rezervasyonunuz iptal edilecek.<br>' +
            'İade edilecek tutar: <strong>' + formatPrice(data.total || 0) + '</strong><br>' +
            '<span style="font-size:13px">Bu işlem geri alınamaz.</span>' +
            '</p>' +
            '<div class="dh-confirm-actions">' +
            '<button type="button" class="dh-confirm-cancel" id="cancel-no">Vazgeç</button>' +
            '<button type="button" class="dh-btn-primary dh-confirm-yes" id="cancel-yes">İptal et</button>' +
            '</div>' +
            '</div>';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        function close() {
            document.body.removeChild(overlay);
            document.body.style.overflow = '';
        }

        overlay.querySelector('#cancel-no').addEventListener('click', close);
        overlay.querySelector('.dh-confirm-backdrop').addEventListener('click', close);

        overlay.querySelector('#cancel-yes').addEventListener('click', function () {
            this.disabled = true;
            this.textContent = 'İşleniyor...';
            performCancel(close);
        });
    }

    function performCancel(done) {
        fetch('/api/tickets/cancel', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ pnr: pnr, last_name: lastName })
        })
            .then(readJson)
            .then(function (result) {
                done();

                if (!result.ok) {
                    renderError(result.data.error || 'İptal işlemi tamamlanamadı.');
                    return;
                }

                render(result.data);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            })
            .catch(function () {
                done();
                renderError('İptal işlemi sırasında bir hata oluştu.');
            });
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
        return data.legs.length ? data.legs[0].tickets.length : 0;
    }

    function checkInUrl() {
        return '/check-in?pnr=' + encodeURIComponent(pnr) +
            '&last_name=' + encodeURIComponent(lastName);
    }

    function detailRow(labelText, value) {
        return '<div class="dh-detail-row">' +
            '<span class="dh-detail-label">' + labelText + '</span>' +
            '<span class="dh-detail-value">' + value + '</span>' +
            '</div>';
    }

    function formatPrice(value) {
        return new Intl.NumberFormat('tr-TR').format(value || 0) + '₺';
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

    function renderError(message) {
        view.innerHTML =
            '<div class="dh-checkin-card dh-checkin-card-error">' +
            '<i class="ti ti-alert-circle dh-checkin-error-icon" aria-hidden="true"></i>' +
            '<p class="dh-checkin-error-text">' + esc(message) + '</p>' +
            '<a href="/" class="dh-btn-primary dh-checkin-error-btn">Ana sayfaya dön</a>' +
            '</div>';
    }
});
