document.addEventListener('DOMContentLoaded', function () {
    var returnField = document.getElementById('return-date-field');
    var departureInput = document.getElementById('departure-date');
    var returnInput = document.getElementById('return-date');
    var departureField = departureInput.closest('.dh-date-field');
    var tripTypeRadios = document.querySelectorAll('input[name="trip_type"]');

    if (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.tr) {
        flatpickr.localize(flatpickr.l10ns.tr);
    }

    // Uçuş takvimimiz bugünden itibaren 90 gün ileriye üretiliyor
    var maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 89);

    // Dar ekranda iki ay yan yana sığmıyor
    var monthCount = window.innerWidth < 760 ? 1 : 2;

    function markRange(instance, startDate, endDate) {
        if (!startDate || !endDate) return;
        var days = instance.days ? instance.days.querySelectorAll('.flatpickr-day') : [];
        // showMonths > 1 için tüm ay containerlarını dolaş
        var allDays = instance.calendarContainer.querySelectorAll('.flatpickr-day');
        allDays.forEach(function (dayEl) {
            dayEl.classList.remove('dh-in-range', 'dh-range-start', 'dh-range-end');
            var dateStr = dayEl.getAttribute('aria-label');
            if (!dateStr) return;
            var d = new Date(dayEl.dateObj);
            if (!d) return;
            if (d > startDate && d < endDate) {
                dayEl.classList.add('dh-in-range');
            }
            if (d.toDateString() === startDate.toDateString()) {
                dayEl.classList.add('dh-range-start');
            }
            if (d.toDateString() === endDate.toDateString()) {
                dayEl.classList.add('dh-range-end');
            }
        });
    }

    function refreshRangeHighlight() {
        var dep = departurePicker.selectedDates[0];
        var ret = returnPicker.selectedDates[0];
        if (!dep || !ret) return;
        if (departurePicker.isOpen) markRange(departurePicker, dep, ret);
        if (returnPicker.isOpen) markRange(returnPicker, dep, ret);
    }

    function buildOptions(anchorEl, position, extra) {
        return Object.assign({
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'j M, D',
            minDate: 'today',
            maxDate: maxDate,
            showMonths: monthCount,
            // Takvimi input'a değil, 60px'lik alan kutusunun tamamına hizala
            positionElement: anchorEl,
            position: position
        }, extra || {});
    }

    var returnPicker = flatpickr(returnInput, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'j M, D',
        minDate: 'today',
        maxDate: maxDate,
        showMonths: 2,
        positionElement: returnField,
        position: 'below right',
        onOpen: refreshRangeHighlight,
        onValueUpdate: refreshRangeHighlight,
        onMonthChange: refreshRangeHighlight,
        onYearChange: refreshRangeHighlight
    });

    var departurePicker = flatpickr(departureInput, {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'j M, D',
        minDate: 'today',
        maxDate: maxDate,
        showMonths: 2,
        positionElement: departureField,
        position: 'below center',
        onOpen: refreshRangeHighlight,
        onValueUpdate: refreshRangeHighlight,
        onMonthChange: refreshRangeHighlight,
        onYearChange: refreshRangeHighlight,
        onChange: function (selectedDates) {
            if (selectedDates.length) {
                returnPicker.set('minDate', selectedDates[0]);
                if (returnPicker.selectedDates.length && returnPicker.selectedDates[0] < selectedDates[0]) {
                    returnPicker.clear();
                }
            }
        }
    });

    function applyTripType(value) {
        if (value === 'one_way') {
            returnField.hidden = true;
            returnPicker.clear();
        } else {
            returnField.hidden = false;
        }
    }

    tripTypeRadios.forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (radio.checked) {
                applyTripType(radio.value);
            }
        });
    });

    var checked = document.querySelector('input[name="trip_type"]:checked');
    if (checked) {
        applyTripType(checked.value);
    }
});
