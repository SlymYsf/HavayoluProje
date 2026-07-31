/**
 * flight-status.js — Ana sayfadaki "Uçuş durumu" sekmesi.
 *
 * Dört arama türü: uçuş numarası, kalkış havalimanı, varış havalimanı ve
 * güzergâh. Havalimanı alanları DHAirportPicker ile çalışır; saat aralığı
 * yalnızca kalkış/varış aramasında görünür — uçuş numarasında tek sonuç
 * olduğu için, güzergâhta ise sonuç sayısı zaten az.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    var form = document.getElementById('status-form');
    if (!form) return;

    var filterValue = document.getElementById('status-filter');
    var filterTrigger = document.getElementById('status-filter-trigger');
    var filterPanel = document.getElementById('status-filter-panel');
    var filterText = document.getElementById('status-filter-text');

    var slotValue = document.getElementById('status-slot');
    var slotTrigger = document.getElementById('status-slot-trigger');
    var slotPanel = document.getElementById('status-slot-panel');
    var slotText = document.getElementById('status-slot-text');
    var slotRange = document.getElementById('status-slot-range');
    var slotLabel = document.getElementById('status-slot-label');

    var numberInput = document.getElementById('status-number');
    var airportHidden = document.getElementById('status-airport');
    var airportLabel = document.getElementById('status-airport-label');
    var originHidden = document.getElementById('status-origin');
    var destinationHidden = document.getElementById('status-destination');
    var dateInput = document.getElementById('status-date');
    var errorBox = document.getElementById('status-error');
    var errorText = document.getElementById('status-error-text');

    initPickers();
    initDropdown(filterTrigger, filterPanel, onFilterSelect);
    initDropdown(slotTrigger, slotPanel, onSlotSelect);
    initDatePicker();
    applyFilter();

    numberInput.addEventListener('input', function () {
        numberInput.value = numberInput.value.replace(/\D/g, '').slice(0, 4);
        hideError();
    });

    form.addEventListener('submit', onSubmit);

    // ================= HAVALİMANI SEÇİCİLER =================

    function initPickers() {
        DHAirportPicker.attach({
            input: document.getElementById('status-airport-search'),
            hidden: airportHidden,
            dropdown: document.getElementById('status-airport-dropdown'),
            onSelect: hideError
        });

        DHAirportPicker.attach({
            input: document.getElementById('status-origin-search'),
            hidden: originHidden,
            dropdown: document.getElementById('status-origin-dropdown'),
            onSelect: hideError
        });

        DHAirportPicker.attach({
            input: document.getElementById('status-destination-search'),
            hidden: destinationHidden,
            dropdown: document.getElementById('status-destination-dropdown'),
            onSelect: hideError
        });
    }

    // ================= AÇILIR LİSTELER =================

    /** Tarayıcının yerleşik select'i yerine özel panel; diğer bileşenlerle tutarlı. */
    function initDropdown(trigger, panel, onSelect) {
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            panel.hidden ? open() : close();
        });

        panel.querySelectorAll('.dh-filter-item').forEach(function (item) {
            item.addEventListener('click', function () {
                onSelect(item);
                close();
            });
        });

        document.addEventListener('click', function (e) {
            if (!panel.hidden && !panel.contains(e.target) && !trigger.contains(e.target)) {
                close();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) close();
        });

        function open() {
            panel.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
        }

        function close() {
            panel.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        }
    }

    function onFilterSelect(item) {
        filterValue.value = item.dataset.value;
        filterText.textContent = item.textContent.trim();
        applyFilter();
    }

    function onSlotSelect(item) {
        slotValue.value = item.dataset.value;
        slotText.textContent = item.dataset.name;
        slotRange.textContent = item.dataset.range;
        hideError();
    }

    // ================= ALAN GÖRÜNÜRLÜĞÜ =================

    function applyFilter() {
        var filter = filterValue.value;

        form.querySelectorAll('.dh-status-input').forEach(function (field) {
            field.hidden = field.dataset.for.split(' ').indexOf(filter) === -1;
        });

        // Kalkış ve varış aynı alanları paylaşıyor, yalnızca etiketler değişiyor
        if (filter === 'departure') {
            airportLabel.textContent = 'Kalkış havalimanı';
            slotLabel.textContent = 'Kalkış saati';
        } else if (filter === 'arrival') {
            airportLabel.textContent = 'Varış havalimanı';
            slotLabel.textContent = 'Varış saati';
        }

        hideError();
    }

    // ================= TARİH =================

    function initDatePicker() {
        if (!window.flatpickr) return;

        flatpickr(dateInput, {
            dateFormat: 'd.m.Y',
            defaultDate: 'today',
            // Uçuş programı ileriye dönük üretiliyor; geçmiş sorgular için
            // bir haftalık pencere yeterli.
            minDate: new Date(Date.now() - 7 * 86400000),
            maxDate: new Date(Date.now() + 365 * 86400000),
            disableMobile: true,
            onChange: hideError
        });
    }

    // ================= GÖNDERİM =================

    function onSubmit(e) {
        e.preventDefault();
        hideError();

        var filter = filterValue.value;
        var iso = toIsoDate(dateInput.value.trim());

        if (!iso) {
            showError('Lütfen bir tarih seçin.');
            return;
        }

        var params = { filter: filter, date: iso };

        if (filter === 'number') {
            var digits = numberInput.value.trim();
            if (!digits) {
                showError('Uçuş numarasını girin.');
                return;
            }
            params.flight_number = 'DH' + digits.padStart(4, '0');

        } else if (filter === 'departure' || filter === 'arrival') {
            if (!airportHidden.value) {
                showError('Havalimanı seçin.');
                return;
            }
            params.airport = airportHidden.value;
            if (slotValue.value) params.time_slot = slotValue.value;

        } else {
            if (!originHidden.value || !destinationHidden.value) {
                showError('Kalkış ve varış noktasını seçin.');
                return;
            }
            if (originHidden.value === destinationHidden.value) {
                showError('Kalkış ve varış noktası aynı olamaz.');
                return;
            }
            params.origin = originHidden.value;
            params.destination = destinationHidden.value;
        }

        window.location.href = '/ucus-durumu?' + new URLSearchParams(params).toString();
    }

    // ================= YARDIMCILAR =================

    /** GG.AA.YYYY → YYYY-AA-GG; sunucu tarafı ISO bekliyor. */
    function toIsoDate(value) {
        var m = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(value);
        return m ? m[3] + '-' + m[2] + '-' + m[1] : null;
    }

    function showError(message) {
        errorText.textContent = message;
        errorBox.hidden = false;
    }

    function hideError() {
        errorBox.hidden = true;
    }
});
