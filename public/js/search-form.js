document.addEventListener('DOMContentLoaded', function () {
    var searchForm = document.getElementById('search-form');
    var resultsBox = document.getElementById('flight-results');
    var swapBtn = document.getElementById('swap-airports');

    var modal = document.getElementById('destinations-modal');
    var modalClose = document.getElementById('modal-close');
    var modalBackdrop = document.getElementById('modal-backdrop');
    var countryList = document.getElementById('country-list');
    var airportList = document.getElementById('airport-list');
    var countryCount = document.getElementById('country-count');
    var airportCount = document.getElementById('airport-count');

    var allAirports = [];
    var searchItems = [];
    var activeField = null;

    var fields = {
        origin: {
            input: document.getElementById('origin-search'),
            hidden: document.getElementById('origin'),
            dropdown: document.getElementById('origin-dropdown')
        },
        destination: {
            input: document.getElementById('destination-search'),
            hidden: document.getElementById('destination'),
            dropdown: document.getElementById('destination-dropdown')
        }
    };

    fetch('/api/airports')
        .then(function (res) { return res.json(); })
        .then(function (airports) {
            allAirports = airports;
            searchItems = buildSearchItems();

            var hub = airports.find(function (a) { return a.is_hub; });
            if (hub) {
                setField('origin', {
                    label: hub.city + ' (' + hub.iata_code + ')',
                    ids: [hub.id]
                });
            }

            buildCountryList();
        })
        .catch(function () {
            resultsBox.innerHTML = '<p class="dh-msg">Havalimanı listesi yüklenemedi.</p>';
        });

    function buildSearchItems() {
        var groups = {};
        allAirports.forEach(function (a) {
            if (!groups[a.city]) groups[a.city] = [];
            groups[a.city].push(a);
        });

        var items = [];
        Object.keys(groups).forEach(function (city) {
            var group = groups[city];

            if (group.length > 1) {
                items.push({
                    type: 'city',
                    label: city + ' (Tümü)',
                    city: city,
                    country: group[0].country,
                    ids: group.map(function (a) { return a.id; })
                });
            }

            group.forEach(function (a) {
                items.push({
                    type: 'airport',
                    label: a.city + ' (' + a.iata_code + ')',
                    city: a.city,
                    country: a.country,
                    ids: [a.id]
                });
            });
        });

        items.sort(function (a, b) { return a.city.localeCompare(b.city, 'tr'); });
        return items;
    }

    Object.keys(fields).forEach(function (key) {
        var field = fields[key];

        field.input.addEventListener('focus', function () {
            activeField = key;
            renderDropdown(key, field.input.value);
        });

        field.input.addEventListener('input', function () {
            activeField = key;
            field.hidden.value = '';
            renderDropdown(key, field.input.value);
        });
    });

    document.addEventListener('click', function (e) {
        Object.keys(fields).forEach(function (key) {
            var field = fields[key];
            if (!field.input.contains(e.target) && !field.dropdown.contains(e.target)) {
                field.dropdown.hidden = true;
            }
        });
    });

    function renderDropdown(key, query) {
        var field = fields[key];
        field.dropdown.innerHTML = '';
        query = query.trim().toLocaleLowerCase('tr');

        if (query === '') {
            var allBtn = document.createElement('div');
            allBtn.className = 'dh-autocomplete-all';
            allBtn.innerHTML = '<i class="ti ti-world" aria-hidden="true"></i> Tüm uçuş noktalarını gör';
            allBtn.addEventListener('click', openModal);
            field.dropdown.appendChild(allBtn);
            field.dropdown.hidden = false;
            return;
        }

        var matches = searchItems.filter(function (item) {
            return item.city.toLocaleLowerCase('tr').indexOf(query) === 0
                || item.country.toLocaleLowerCase('tr').indexOf(query) === 0;
        });

        if (!matches.length) {
            var empty = document.createElement('div');
            empty.className = 'dh-autocomplete-empty';
            empty.textContent = 'Eşleşen uçuş noktası bulunamadı.';
            field.dropdown.appendChild(empty);
            field.dropdown.hidden = false;
            return;
        }

        matches.forEach(function (item) {
            var el = document.createElement('div');
            el.className = 'dh-autocomplete-item';
            var icon = item.type === 'city' ? 'ti-world' : 'ti-plane';
            el.innerHTML =
                '<div class="dh-autocomplete-city"><i class="ti ' + icon + ' dh-autocomplete-icon" aria-hidden="true"></i>' + item.label + '</div>' +
                '<div class="dh-autocomplete-meta">' + item.country + '</div>';
            el.addEventListener('click', function () {
                setField(key, item);
                field.dropdown.hidden = true;
            });
            field.dropdown.appendChild(el);
        });

        field.dropdown.hidden = false;
    }

    function setField(key, item) {
        fields[key].hidden.value = item.ids.join(',');
        fields[key].input.value = item.label;
    }

    function buildCountryList() {
        var countries = [];
        allAirports.forEach(function (a) {
            if (countries.indexOf(a.country) === -1) {
                countries.push(a.country);
            }
        });
        countries.sort(function (a, b) { return a.localeCompare(b, 'tr'); });

        countryCount.textContent = countries.length;
        countryList.innerHTML = '';

        var currentLetter = '';
        countries.forEach(function (country) {
            var letter = country.charAt(0).toLocaleUpperCase('tr');
            if (letter !== currentLetter) {
                currentLetter = letter;
                var header = document.createElement('div');
                header.className = 'dh-modal-letter';
                header.textContent = letter;
                countryList.appendChild(header);
            }

            var item = document.createElement('div');
            item.className = 'dh-modal-item';
            item.textContent = country;
            item.addEventListener('click', function () {
                document.querySelectorAll('#country-list .dh-modal-item').forEach(function (el) {
                    el.classList.remove('dh-modal-item-active');
                });
                item.classList.add('dh-modal-item-active');
                showAirportsOf(country);
            });
            countryList.appendChild(item);
        });

        airportList.innerHTML = '<div class="dh-modal-hint">Soldan bir ülke seçin.</div>';
    }

    function showAirportsOf(country) {
        var list = searchItems.filter(function (item) { return item.country === country; });
        airportCount.textContent = list.filter(function (i) { return i.type === 'airport'; }).length;
        airportList.innerHTML = '';

        list.forEach(function (item) {
            var el = document.createElement('div');
            el.className = 'dh-modal-item';
            var icon = item.type === 'city' ? 'ti-world' : 'ti-plane';
            el.innerHTML = '<i class="ti ' + icon + ' dh-autocomplete-icon" aria-hidden="true"></i>' + item.label;
            el.addEventListener('click', function () {
                if (activeField) {
                    setField(activeField, item);
                }
                closeModal();
            });
            airportList.appendChild(el);
        });
    }

    function openModal() {
        Object.keys(fields).forEach(function (key) {
            fields[key].dropdown.hidden = true;
        });
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';
    }

    modalClose.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });

    swapBtn.addEventListener('click', function () {
        var originId = fields.origin.hidden.value;
        var originText = fields.origin.input.value;

        fields.origin.hidden.value = fields.destination.hidden.value;
        fields.origin.input.value = fields.destination.input.value;

        fields.destination.hidden.value = originId;
        fields.destination.input.value = originText;
    });

    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var originIds = fields.origin.hidden.value ? fields.origin.hidden.value.split(',') : [];
        var destinationIds = fields.destination.hidden.value ? fields.destination.hidden.value.split(',') : [];
        var date = document.getElementById('departure-date').value;

        if (!originIds.length || !destinationIds.length) {
            resultsBox.innerHTML = '<p class="dh-msg">Lütfen kalkış ve varış noktalarını listeden seçin.</p>';
            return;
        }

        var requests = [];
        originIds.forEach(function (originId) {
            destinationIds.forEach(function (destId) {
                if (originId === destId) return;

                var url = '/api/flights/search?origin_airport_id=' + originId + '&destination_airport_id=' + destId;
                if (date) {
                    url += '&date=' + date;
                }

                requests.push(
                    fetch(url)
                        .then(function (res) { return res.json(); })
                        .then(function (data) { return Array.isArray(data) ? data : []; })
                        .catch(function () { return []; })
                );
            });
        });

        if (!requests.length) {
            resultsBox.innerHTML = '<p class="dh-msg">Kalkış ve varış noktası aynı olamaz.</p>';
            return;
        }

        resultsBox.innerHTML = '<p class="dh-msg">Uçuşlar aranıyor...</p>';

        Promise.all(requests).then(function (responses) {
            var merged = [];
            responses.forEach(function (list) {
                merged = merged.concat(list);
            });

            merged.sort(function (a, b) {
                return new Date(a.flight.departure_time) - new Date(b.flight.departure_time);
            });

            renderFlights(merged);
        });
    });

    function renderFlights(results) {
        if (!results.length) {
            resultsBox.innerHTML = '<p class="dh-msg">Seçtiğiniz kriterlere uygun uçuş bulunamadı.</p>';
            return;
        }

        resultsBox.innerHTML = '<h2 class="dh-results-title">' + results.length + ' uçuş bulundu</h2>';

        results.forEach(function (item) {
            resultsBox.appendChild(buildFlightCard(item));
        });
    }

    function buildFlightCard(item) {
        var flight = item.flight;
        var card = document.createElement('div');
        card.className = 'dh-flight-card';

        var depTime = formatTime(flight.departure_time);
        var arrTime = formatTime(flight.arrival_time);
        var originCode = flight.route.origin_airport ? flight.route.origin_airport.iata_code : '';
        var destCode = flight.route.destination_airport ? flight.route.destination_airport.iata_code : '';

        card.innerHTML =
            '<div class="dh-flight-time">' + depTime + '</div>' +
            '<div class="dh-flight-path">' +
            '<span class="dh-flight-path-code">' + originCode + '</span>' +
            '<span class="dh-flight-path-line"><i class="ti ti-plane-departure dh-plane-icon" aria-hidden="true"></i></span>' +
            '<span class="dh-flight-path-code">' + destCode + '</span>' +
            '</div>' +
            '<div class="dh-flight-time">' + arrTime + '</div>' +
            '<span class="dh-aircraft-badge">' + flight.aircraft.model + '</span>' +
            '<span class="dh-flight-number">' + flight.flight_number + '</span>';

        Object.keys(item.fares).forEach(function (cabinClass) {
            var pill = document.createElement('span');
            pill.className = 'dh-fare-pill' + (cabinClass === 'business' ? ' dh-fare-pill-business' : '');
            pill.textContent = cabinLabel(cabinClass) + ' ' + formatPrice(item.fares[cabinClass]);
            card.appendChild(pill);
        });

        return card;
    }

    function cabinLabel(cabinClass) {
        if (cabinClass === 'economy') return 'Economy';
        if (cabinClass === 'business') return 'Business';
        if (cabinClass === 'premium_economy') return 'Premium';
        return cabinClass;
    }

    function formatTime(isoString) {
        var d = new Date(isoString);
        return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
    }

    function formatPrice(value) {
        return Math.round(value).toLocaleString('tr-TR') + '₺';
    }
});
