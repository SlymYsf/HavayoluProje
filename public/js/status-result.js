/**
 * status-result.js — Uçuş durumu sonuç sayfası (/ucus-durumu)
 *
 * Tek sonuçta detaylı kart, birden çok sonuçta kompakt liste gösterir.
 * Rezervasyon gerektirmez; herkese açık bir sorgudur.
 *
 * Rötarlı uçuşlarda gösterilen saat tahmini kalkış/varış saatidir; planlanan
 * saat sunucudan ayrıca geliyor ancak ekranda tek saat gösteriliyor, gecikme
 * bilgisi rozet ve uyarı kutusunda veriliyor.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var view = document.getElementById('status-view');
    var params = new URLSearchParams(window.location.search);

    var t = window.dhT || function (key) { return key; };
    var locale = window.dhLocale === 'en' ? 'en-GB' : 'tr-TR';

    var STATUS_INFO = {
        'Planlandı':  { cls: '', icon: 'ti-clock', note: t('Uçuş planlandığı saatte gerçekleşecek.') },
        'Gecikmeli':  { cls: 'dh-status-delayed', icon: 'ti-alert-triangle', note: t('Uçuşta gecikme bildirildi. Güncel saati kontrol edin.') },
        'İptal':      { cls: 'dh-status-cancelled', icon: 'ti-x', note: t('Bu uçuş iptal edilmiştir. Bilet sahiplerine bilgilendirme yapılır.') },
        'Tamamlandı': { cls: 'dh-status-done', icon: 'ti-circle-check', note: t('Uçuş tamamlandı.') }
    };

    var STATUS_LABELS = {
        'Planlandı':  t('Planlandı'),
        'Gecikmeli':  t('Gecikmeli'),
        'İptal':      t('İptal'),
        'Tamamlandı': t('Tamamlandı')
    };

    var FILTER_LABELS = {
        number:    t('Uçuş numarası'),
        departure: t('Kalkış havalimanı'),
        arrival:   t('Varış havalimanı'),
        route:     t('Güzergâh')
    };

    if (!params.get('filter') || !params.get('date')) {
        renderError(t('Arama bilgileri eksik. Lütfen ana sayfadan tekrar deneyin.'));
        return;
    }

    load();

    function load() {
        fetch('/api/flights/status?' + params.toString())
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, data: data };
                });
            })
            .then(function (result) {
                if (!result.ok) {
                    renderError(result.data.error || t('Uçuş bulunamadı.'));
                    return;
                }
                render(result.data);
            })
            .catch(function () {
                renderError(t('Uçuş bilgileri alınamadı. Lütfen tekrar deneyin.'));
            });
    }

    function render(data) {
        var html = renderHeader(data);

        // Tek sonuçta detaylı kart, çoklu sonuçta liste: tek uçuş için
        // liste satırı bilgi kaybı, elli uçuş için detaylı kart okunaksız.
        if (data.count === 1) {
            html += renderDetail(data.flights[0]);
        } else {
            html += renderList(data.flights);
        }

        html += renderActions();
        view.innerHTML = html;
    }

    function renderHeader(data) {
        return '<div class="dh-checkin-card dh-status-summary">' +
            '<div>' +
            '<span class="dh-selection-label">' + (FILTER_LABELS[data.filter] || t('Arama')) + '</span>' +
            '<div class="dh-status-summary-date">' + formatDate(data.date) + '</div>' +
            '</div>' +
            '<span class="dh-checkin-badge">' + data.count + ' ' + t('uçuş') + '</span>' +
            '</div>';
    }

    /** Rötar varsa tahmini saat, yoksa planlanan saat. */
    function departureOf(f) {
        return f.delay ? f.delay.estimated_departure : f.departure_time;
    }

    function arrivalOf(f) {
        return f.delay ? f.delay.estimated_arrival : f.arrival_time;
    }

    /** Tek uçuş: saatler, rota çizgisi ve havalimanı detayları. */
    function renderDetail(f) {
        var info = STATUS_INFO[f.status] || { cls: '', icon: 'ti-info-circle', note: '' };
        var dep = departureOf(f);
        var arr = arrivalOf(f);
        var dayDiff = dayOffset(dep, arr);

        return '<div class="dh-checkin-card">' +

            '<div class="dh-checkin-card-header">' +
            '<span class="dh-checkin-badge ' + info.cls + '">' +
            '<i class="ti ' + info.icon + '" aria-hidden="true"></i> ' + statusLabel(f) +
            '</span>' +
            '<span class="dh-checkin-pnr">' + esc(f.flight_number) + '</span>' +
            '</div>' +

            '<div class="dh-status-route">' +
            '<div class="dh-status-endpoint">' +
            '<span class="dh-status-time">' + formatTime(dep) + '</span>' +
            '<span class="dh-status-code">' + esc(f.origin.iata_code) + '</span>' +
            '<span class="dh-status-city">' + esc(f.origin.city) + '</span>' +
            '</div>' +

            '<div class="dh-status-middle">' +
            '<span class="dh-status-duration">' + formatDuration(f.duration_min) + '</span>' +
            '<div class="dh-status-line"><i class="ti ti-plane" aria-hidden="true"></i></div>' +
            '</div>' +

            '<div class="dh-status-endpoint dh-status-endpoint-right">' +
            '<span class="dh-status-time">' + formatTime(arr) +
            (dayDiff > 0 ? '<span class="dh-day-offset">+' + dayDiff + ' ' + t('gün') + '</span>' : '') +
            '</span>' +
            '<span class="dh-status-code">' + esc(f.destination.iata_code) + '</span>' +
            '<span class="dh-status-city">' + esc(f.destination.city) + '</span>' +
            '</div>' +
            '</div>' +

            '<div class="dh-checkin-details">' +
            detailRow(t('Kalkış havalimanı'), esc(f.origin.name) + ' · ' + esc(f.origin.country)) +
            detailRow(t('Varış havalimanı'), esc(f.destination.name) + ' · ' + esc(f.destination.country)) +
            detailRow(t('Uçak'), esc(f.aircraft)) +
            (f.delay ? detailRow(t('Gecikme sebebi'), esc(f.delay.reason)) : '') +
            '</div>' +

            '</div>' +

            renderNote(f, info);
    }

    /**
     * Rötarlı uçuşlarda genel uyarı yerine süre ve planlanan saati içeren
     * özel metin gösteriliyor; "gecikme bildirildi" tek başına yolcuya ne
     * kadar beklemesi gerektiğini söylemiyordu.
     */
    function renderNote(f, info) {
        var text = info.note;

        if (f.delay) {
            text = f.delay.reason + ' ' + t('sebebiyle') + ' ' + f.delay.minutes + ' ' +
                t('dakika gecikme bildirildi.') + ' ' +
                t('Planlanan kalkış:') + ' ' + formatTime(f.departure_time) + '.';
        }

        if (!text) return '';

        return '<div class="dh-checkin-note ' + info.cls + '">' +
            '<i class="ti ' + info.icon + '" aria-hidden="true"></i>' +
            '<span>' + esc(text) + '</span>' +
            '</div>';
    }

    /** Gecikmeli uçuşlarda rozette süre de gösteriliyor. */
    function statusLabel(f) {
        var label = STATUS_LABELS[f.status] || f.status;

        return f.delay
            ? esc(label) + ' · ' + f.delay.minutes + ' ' + t('dk')
            : esc(label);
    }

    /** Çoklu sonuç: her uçuş tek satır. */
    function renderList(flights) {
        var html = '<div class="dh-checkin-card">';

        flights.forEach(function (f) {
            var info = STATUS_INFO[f.status] || { cls: '', icon: 'ti-info-circle' };
            var dep = departureOf(f);
            var arr = arrivalOf(f);
            var dayDiff = dayOffset(dep, arr);

            html +=
                '<div class="dh-status-row">' +
                '<span class="dh-status-row-number">' + esc(f.flight_number) + '</span>' +
                '<span class="dh-status-row-time">' + formatTime(dep) + '</span>' +
                '<span class="dh-status-row-route">' +
                esc(f.origin.iata_code) + ' → ' + esc(f.destination.iata_code) +
                '</span>' +
                '<span class="dh-status-row-time">' + formatTime(arr) +
                (dayDiff > 0 ? '<span class="dh-day-offset">+' + dayDiff + '</span>' : '') +
                '</span>' +
                '<span class="dh-checkin-badge ' + info.cls + ' dh-status-row-badge">' +
                statusLabel(f) +
                '</span>' +
                '</div>';
        });

        return html + '</div>';
    }

    function renderActions() {
        return '<div class="dh-checkin-actions">' +
            '<a href="/" class="dh-checkin-back">' + t('Yeni sorgulama') + '</a>' +
            '<a href="/check-in" class="dh-btn-primary dh-checkin-error-btn">' +
            t('Check-in\'e git') + ' <i class="ti ti-arrow-right" aria-hidden="true"></i></a>' +
            '</div>';
    }

    // ================= YARDIMCILAR =================

    function esc(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function detailRow(labelText, value) {
        return '<div class="dh-detail-row">' +
            '<span class="dh-detail-label">' + labelText + '</span>' +
            '<span class="dh-detail-value">' + value + '</span>' +
            '</div>';
    }

    /** Varış ertesi güne sarkıyorsa kaç gün — takvim günü farkı. */
    function dayOffset(depIso, arrIso) {
        var dep = new Date(depIso);
        var arr = new Date(arrIso);

        dep.setHours(0, 0, 0, 0);
        arr.setHours(0, 0, 0, 0);

        return Math.round((arr - dep) / 86400000);
    }

    /**
     * Süre kısaltmaları dile göre değişiyor: Türkçe'de 3s 45d,
     * İngilizce'de 3h 45m.
     */
    function formatDuration(minutes) {
        var h = Math.floor(minutes / 60);
        var m = minutes % 60;
        var hourUnit = t('sa');
        var minuteUnit = t('dk');

        if (h && m) return h + hourUnit + ' ' + String(m).padStart(2, '0') + minuteUnit;
        if (h) return h + hourUnit;
        return m + minuteUnit;
    }

    function formatDate(iso) {
        if (!iso) return '—';
        return new Date(iso).toLocaleDateString(locale, {
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
            '<a href="/" class="dh-btn-primary dh-checkin-error-btn">' + t('Ana sayfaya dön') + '</a>' +
            '</div>';
    }
});
