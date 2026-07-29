window.DHFlightRender = (function () {
    function cabinLabel(cabinClass) {
        if (cabinClass === 'economy') return 'Economy';
        if (cabinClass === 'business') return 'Business';
        if (cabinClass === 'premium_economy') return 'Premium';
        return cabinClass;
    }

    var CABIN_ORDER = ['economy', 'premium_economy', 'business'];

    function sortedCabins(fares) {
        return Object.keys(fares).sort(function (a, b) {
            var ia = CABIN_ORDER.indexOf(a);
            var ib = CABIN_ORDER.indexOf(b);
            return (ia === -1 ? 99 : ia) - (ib === -1 ? 99 : ib);
        });
    }

    function formatTime(isoString) {
        var d = new Date(isoString);
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function formatPrice(value) {
        return Math.round(value).toLocaleString('tr-TR') + '₺';
    }

    function dayOffsetLabel(depIso, arrIso) {
        var dep = new Date(depIso);
        var arr = new Date(arrIso);
        var depDay = Date.UTC(dep.getFullYear(), dep.getMonth(), dep.getDate());
        var arrDay = Date.UTC(arr.getFullYear(), arr.getMonth(), arr.getDate());
        var diff = Math.round((arrDay - depDay) / 86400000);
        return diff > 0 ? '<span class="dh-day-offset">+' + diff + ' gün</span>' : '';
    }

    function durationLabel(depIso, arrIso) {
        var diffMs = new Date(arrIso) - new Date(depIso);
        if (diffMs <= 0) return '';
        var totalMinutes = Math.round(diffMs / 60000);
        var hours = Math.floor(totalMinutes / 60);
        var minutes = totalMinutes % 60;
        return hours + 's ' + String(minutes).padStart(2, '0') + 'd';
    }

    function buildFlightCard(item) {
        var flight = item.flight;
        var card = document.createElement('div');
        card.className = 'dh-flight-card';

        var depTime = formatTime(flight.departure_time);
        var arrTime = formatTime(flight.arrival_time);
        var originCode = flight.route.origin_airport ? flight.route.origin_airport.iata_code : '';
        var destCode = flight.route.destination_airport ? flight.route.destination_airport.iata_code : '';

        var summary = document.createElement('div');
        summary.className = 'dh-flight-summary';
        summary.innerHTML =
            '<div class="dh-flight-time">' + depTime + '</div>' +
            '<div class="dh-flight-path">' +
            '<span class="dh-flight-path-code">' + originCode + '</span>' +
            '<span class="dh-flight-path-line">' +
            '<i class="ti ti-plane-departure dh-plane-icon" aria-hidden="true"></i>' +
            '<span class="dh-flight-duration">' + durationLabel(flight.departure_time, flight.arrival_time) + '</span>' +
            '</span>' +
            '<span class="dh-flight-path-code">' + destCode + '</span>' +
            '</div>' +
            '<div class="dh-flight-time">' + arrTime + dayOffsetLabel(flight.departure_time, flight.arrival_time) + '</div>' +
            '<div class="dh-flight-meta">' +
            '<span class="dh-aircraft-badge">' + flight.aircraft.model + '</span>' +
            '<span class="dh-flight-number">' + flight.flight_number + '</span>' +
            '</div>';
        card.appendChild(summary);

        var fareGroup = document.createElement('div');
        fareGroup.className = 'dh-flight-fares';

        sortedCabins(item.fares).forEach(function (cabinClass) {
            var fare = item.fares[cabinClass];

            var option = document.createElement('button');
            option.type = 'button';
            option.className = 'dh-fare-option';
            option.dataset.cabin = cabinClass;
            option.dataset.price = fare.total_price;
            option.dataset.flightNumber = flight.flight_number;

            var perPersonHint = fare.passenger_count > 1
                ? '<span class="dh-fare-option-hint">' + fare.passenger_count + ' yolcu · ' + formatPrice(fare.unit_price) + '/yetişkin</span>'
                : '';

            option.innerHTML =
                '<span class="dh-fare-radio"></span>' +
                '<span class="dh-fare-option-label">' + cabinLabel(cabinClass) + '</span>' +
                '<span class="dh-fare-option-price">' + formatPrice(fare.total_price) + '</span>' +
                perPersonHint;

            option.addEventListener('click', function () {
                fareGroup.querySelectorAll('.dh-fare-option').forEach(function (el) {
                    el.classList.remove('dh-fare-option-selected');
                });
                option.classList.add('dh-fare-option-selected');

                card.dispatchEvent(new CustomEvent('dh:fare-selected', {
                    bubbles: true,
                    detail: {
                        flightId: flight.id,
                        cabin: cabinClass,
                        price: fare.total_price,
                        flightNumber: flight.flight_number,
                        card: card
                    }
                }));
            });

            fareGroup.appendChild(option);
        });

        card.appendChild(fareGroup);
        return card;
    }

    function flatten(responses) {
        var merged = [];
        responses.forEach(function (list) { merged = merged.concat(list); });
        merged.sort(function (a, b) { return new Date(a.flight.departure_time) - new Date(b.flight.departure_time); });
        return merged;
    }

    function fetchFlights(fromIds, toIds, date, cabin, adult, child, infant, student) {
        var requests = [];
        fromIds.forEach(function (fromId) {
            toIds.forEach(function (toId) {
                if (fromId === toId) return;
                var url = '/api/flights/search?origin_airport_id=' + fromId + '&destination_airport_id=' + toId +
                    '&cabin_class=' + cabin + '&adult=' + adult + '&child=' + child + '&infant=' + infant + '&student=' + student;
                if (date) url += '&date=' + date;
                requests.push(
                    fetch(url)
                        .then(function (res) { return res.json(); })
                        .then(function (data) { return Array.isArray(data) ? data : []; })
                        .catch(function () { return []; })
                );
            });
        });
        return Promise.all(requests).then(flatten);
    }

    function appendSection(container, title, results, sectionKey) {
        var heading = document.createElement('h2');
        heading.className = 'dh-results-title';
        heading.textContent = title + ' — ' + results.length + ' sonuç';
        heading.dataset.section = sectionKey || 'outbound';
        container.appendChild(heading);

        if (!results.length) {
            var msg = document.createElement('p');
            msg.className = 'dh-msg';
            msg.textContent = 'Bu yönde uygun uçuş bulunamadı.';
            container.appendChild(msg);
            return;
        }
        results.forEach(function (item) {
            var card = buildFlightCard(item);
            card.dataset.section = sectionKey || 'outbound';
            container.appendChild(card);
        });
    }

    return {
        cabinLabel: cabinLabel,
        formatTime: formatTime,
        formatPrice: formatPrice,
        buildFlightCard: buildFlightCard,
        fetchFlights: fetchFlights,
        appendSection: appendSection
    };
})();
