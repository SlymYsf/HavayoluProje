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
    var destinationItems = null;
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

    // ===== FORM DOĞRULAMA UYARILARI =====
    var errorBox = document.getElementById('search-error');
    var errorText = document.getElementById('search-error-text');

    function showFormError(message, fieldEl) {
        if (errorBox && errorText) {
            errorText.textContent = message;
            errorBox.hidden = false;
        }
        if (fieldEl) {
            fieldEl.classList.add('dh-field-invalid');
            fieldEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    function clearFormError() {
        if (errorBox) errorBox.hidden = true;
        document.querySelectorAll('.dh-field-invalid').forEach(function (el) {
            el.classList.remove('dh-field-invalid');
        });
    }

    fetch('/api/airports')
        .then(function (res) { return res.json(); })
        .then(function (airports) {
            allAirports = airports;
            searchItems = buildItemsFrom(allAirports);
            buildCountryList();
            rehydratePrefilledFields();
        })
        .catch(function () {
            resultsBox.innerHTML = '<p class="dh-msg">Havalimanı listesi yüklenemedi.</p>';
        });

    function buildItemsFrom(airports) {
        var groups = {};
        airports.forEach(function (a) {
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
                    displayCity: city,
                    displaySubtitle: 'Tüm havalimanları',
                    name: city,
                    city: city,
                    country: group[0].country,
                    ids: group.map(function (a) { return a.id; })
                });
            }
            group.forEach(function (a) {
                items.push({
                    type: 'airport',
                    label: a.name + ' (' + a.iata_code + ')',
                    displayCity: a.city,
                    displaySubtitle: a.name + ' (' + a.iata_code + ')',
                    name: a.name,
                    city: a.city,
                    country: a.country,
                    ids: [a.id]
                });
            });
        });
        items.sort(function (a, b) { return a.city.localeCompare(b.city, 'tr'); });
        return items;
    }

    function rehydratePrefilledFields() {
        var snapshot = {};
        ['origin', 'destination'].forEach(function (key) {
            snapshot[key] = {
                label: fields[key].input.value,
                hiddenId: fields[key].hidden.value
            };
        });

        ['origin', 'destination'].forEach(function (key) {
            var snap = snapshot[key];
            if (!snap.label || !snap.hiddenId) return;

            var match = searchItems.find(function (item) {
                return item.label === snap.label;
            });

            if (match) {
                fields[key].selectedItem = match;
                fields[key].hidden.value = snap.hiddenId;
                fields[key].input.value = snap.label;
                renderSelectedDisplay(key, match);

                if (key === 'origin') {
                    loadDestinationsFor(match.ids[0]);
                }
            }
        });
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
            clearSelectedDisplay(key);
            renderDropdown(key, field.input.value);
        });
    });

    // Otomatik tamamlama dropdown dış tıklama — snapshot geri alma dahil
    document.addEventListener('click', function (e) {
        Object.keys(fields).forEach(function (key) {
            var field = fields[key];
            var routeHalf = field.input.closest('.dh-route-half');
            if (!field.input.contains(e.target) &&
                !field.dropdown.contains(e.target) &&
                !routeHalf.contains(e.target)) {
                field.dropdown.hidden = true;

                if (field.snapshot && !field.selectedItem) {
                    setField(key, field.snapshot, { skipLoadDestinations: true });
                    field.snapshot = null;
                }
            }
        });
    });

    function renderDropdown(key, query) {
        var field = fields[key];
        field.dropdown.innerHTML = '';
        query = query.trim().toLocaleLowerCase('tr');

        var pool = (key === 'destination' && destinationItems) ? destinationItems : searchItems;

        if (query === '') {
            var allBtn = document.createElement('div');
            allBtn.className = 'dh-autocomplete-all';
            allBtn.innerHTML = '<i class="ti ti-world" aria-hidden="true"></i> Tüm uçuş noktalarını gör';
            allBtn.addEventListener('click', openModal);
            field.dropdown.appendChild(allBtn);
            field.dropdown.hidden = false;
            return;
        }

        var matches = pool.filter(function (item) {
            return matchesWordStart(item.name, query) || matchesWordStart(item.city, query) || matchesWordStart(item.country, query);
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
                '<div class="dh-autocomplete-meta">' + item.city + ', ' + item.country + '</div>';
            el.addEventListener('click', function () {
                setField(key, item);
                field.dropdown.hidden = true;
            });
            field.dropdown.appendChild(el);
        });

        field.dropdown.hidden = false;
    }

    function setField(key, item, options) {
        clearFormError();
        options = options || {};
        fields[key].hidden.value = item.ids.join(',');
        fields[key].input.value = item.label;
        fields[key].selectedItem = item;
        fields[key].snapshot = null;
        renderSelectedDisplay(key, item);
        if (key === 'origin' && !options.skipLoadDestinations) {
            loadDestinationsFor(item.ids[0]);
        }
    }

    function loadDestinationsFor(originId) {
        clearSelectedDisplay('destination');
        fields.destination.hidden.value = '';
        fields.destination.input.value = '';
        destinationItems = null;

        fetch('/api/airports/' + originId + '/destinations')
            .then(function (res) { return res.json(); })
            .then(function (airports) { destinationItems = buildItemsFrom(airports); })
            .catch(function () { destinationItems = null; });
    }

    function renderSelectedDisplay(key, item) {
        var routeHalf = fields[key].input.closest('.dh-route-half');
        var existing = routeHalf.querySelector('.dh-route-selected');
        if (existing) existing.remove();

        var display = document.createElement('div');
        display.className = 'dh-route-selected';
        display.innerHTML =
            '<span class="dh-route-city">' + item.displayCity + '</span>' +
            '<span class="dh-route-subtitle">' + item.displaySubtitle + '</span>';
        display.addEventListener('click', function (e) {
            e.stopPropagation();
            fields[key].snapshot = fields[key].selectedItem;
            clearSelectedDisplay(key);
            fields[key].hidden.value = '';
            fields[key].input.value = '';
            fields[key].input.focus();
            activeField = key;
            renderDropdown(key, '');
        });
        routeHalf.appendChild(display);
        fields[key].input.classList.add('dh-route-input-hidden');
    }

    function clearSelectedDisplay(key) {
        var routeHalf = fields[key].input.closest('.dh-route-half');
        var existing = routeHalf.querySelector('.dh-route-selected');
        if (existing) existing.remove();
        fields[key].input.classList.remove('dh-route-input-hidden');
        fields[key].selectedItem = null;
    }

    function matchesWordStart(text, query) {
        if (!text) return false;
        var words = text.toLocaleLowerCase('tr').split(/[\s\-\/]+/);
        for (var i = 0; i < words.length; i++) {
            if (words[i].indexOf(query) === 0) return true;
        }
        return false;
    }

    function buildCountryList() {
        var pool = (activeField === 'destination' && destinationItems) ? destinationItems : searchItems;
        var countries = [];
        pool.forEach(function (item) {
            if (item.type === 'airport' && countries.indexOf(item.country) === -1) {
                countries.push(item.country);
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
                document.querySelectorAll('#country-list .dh-modal-item').forEach(function (el) { el.classList.remove('dh-modal-item-active'); });
                item.classList.add('dh-modal-item-active');
                showAirportsOf(country);
            });
            countryList.appendChild(item);
        });

        airportList.innerHTML = '<div class="dh-modal-hint">Soldan bir ülke seçin.</div>';
    }

    function showAirportsOf(country) {
        var pool = (activeField === 'destination' && destinationItems) ? destinationItems : searchItems;
        var list = pool.filter(function (item) { return item.country === country; });
        airportCount.textContent = list.filter(function (i) { return i.type === 'airport'; }).length;
        airportList.innerHTML = '';

        if (!list.length) {
            airportList.innerHTML = '<div class="dh-modal-hint">Seçtiğiniz kalkış noktasından bu ülkeye uçuş bulunmuyor.</div>';
            return;
        }

        list.forEach(function (item) {
            var el = document.createElement('div');
            el.className = 'dh-modal-item';
            var icon = item.type === 'city' ? 'ti-world' : 'ti-plane';
            el.innerHTML = '<i class="ti ' + icon + ' dh-autocomplete-icon" aria-hidden="true"></i>' + item.label;
            el.addEventListener('click', function () {
                if (activeField) setField(activeField, item);
                closeModal();
            });
            airportList.appendChild(el);
        });
    }

    function openModal() {
        Object.keys(fields).forEach(function (key) { fields[key].dropdown.hidden = true; });
        buildCountryList();
        modal.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.hidden = true;
        document.body.style.overflow = '';

        if (activeField && fields[activeField].snapshot && !fields[activeField].selectedItem) {
            setField(activeField, fields[activeField].snapshot, { skipLoadDestinations: true });
            fields[activeField].snapshot = null;
        }
    }

    modalClose.addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeModal(); });

    swapBtn.addEventListener('click', function () {
        var originItem = fields.origin.selectedItem;
        var destItem = fields.destination.selectedItem;

        clearSelectedDisplay('origin');
        clearSelectedDisplay('destination');
        fields.origin.hidden.value = '';
        fields.destination.hidden.value = '';
        fields.origin.input.value = '';
        fields.destination.input.value = '';

        if (destItem) {
            setField('origin', destItem);
        }
        if (originItem) {
            setField('destination', originItem);
        }
    });

    // ===== ARAMA FORMU GÖNDERİMİ =====
    searchForm.addEventListener('submit', function (e) {
        e.preventDefault();
        clearFormError();

        var originIds = fields.origin.hidden.value ? fields.origin.hidden.value.split(',') : [];
        var destinationIds = fields.destination.hidden.value ? fields.destination.hidden.value.split(',') : [];
        var date = document.getElementById('departure-date').value;
        var returnDate = document.getElementById('return-date').value;
        var tripType = document.querySelector('input[name="trip_type"]:checked');
        var isRoundTrip = tripType && tripType.value === 'round_trip';

        var totalPax =
            parseInt(document.getElementById('pax_adult').value || 0) +
            parseInt(document.getElementById('pax_child').value || 0) +
            parseInt(document.getElementById('pax_infant').value || 0) +
            parseInt(document.getElementById('pax_student').value || 0);

        if (!originIds.length) {
            showFormError('Lütfen kalkış noktasını seçin.', fields.origin.input.closest('.dh-route-half'));
            return;
        }

        if (!destinationIds.length) {
            showFormError('Lütfen varış noktasını seçin.', fields.destination.input.closest('.dh-route-half'));
            return;
        }

        if (!date) {
            showFormError('Lütfen gidiş tarihini seçin.', document.getElementById('departure-date').closest('.dh-date-field'));
            return;
        }

        if (isRoundTrip && !returnDate) {
            showFormError('Gidiş-dönüş araması için dönüş tarihini seçin.', document.getElementById('return-date-field'));
            return;
        }

        if (totalPax < 1) {
            showFormError('Lütfen en az bir yolcu seçin.', document.getElementById('passenger-toggle'));
            return;
        }

        var params = new URLSearchParams();
        params.set('origin', fields.origin.hidden.value);
        params.set('destination', fields.destination.hidden.value);
        params.set('origin_label', fields.origin.input.value);
        params.set('destination_label', fields.destination.input.value);
        params.set('date', date);
        if (isRoundTrip) params.set('return_date', returnDate);
        params.set('trip_type', tripType ? tripType.value : 'one_way');
        params.set('cabin_class', document.getElementById('cabin_class').value);
        params.set('adult', document.getElementById('pax_adult').value);
        params.set('child', document.getElementById('pax_child').value);
        params.set('infant', document.getElementById('pax_infant').value);
        params.set('student', document.getElementById('pax_student').value);

        window.location.href = '/ucus-sonuclari?' + params.toString();
    });

    // ===== YOLCU VE KABİN SEÇİMİ (Uygula / Vazgeç mantığı) =====
    const passengerToggle = document.getElementById('passenger-toggle');
    const passengerDropdown = document.getElementById('passenger-dropdown');
    const passengerWrapper = document.getElementById('passenger-wrapper');
    const summaryText = document.getElementById('passenger-summary');
    const paxApplyBtn = document.getElementById('pax-apply');
    const paxCancelBtn = document.getElementById('pax-cancel');

    const cabinRadios = document.querySelectorAll('input[name="cabin_selection"]');
    const cabinInput = document.getElementById('cabin_class');
    const paxButtons = document.querySelectorAll('.dh-pax-btn');

    // Kalıcı state — dropdown kapalıyken de doğru olan değerler
    const committedState = {
        adult: parseInt(document.getElementById('pax_adult').value) || 1,
        child: parseInt(document.getElementById('pax_child').value) || 0,
        infant: parseInt(document.getElementById('pax_infant').value) || 0,
        student: parseInt(document.getElementById('pax_student').value) || 0,
        cabin: cabinInput.value || 'economy'
    };

    // Dropdown açıldığında düzenlenen geçici state
    let draftState = { ...committedState };

    const MAX_TOTAL_PAX = 9;

    function openPaxDropdown() {
        draftState = { ...committedState };
        syncDraftToUI();
        passengerDropdown.hidden = false;
        passengerToggle.classList.add('active');
    }

    function closePaxDropdown() {
        passengerDropdown.hidden = true;
        passengerToggle.classList.remove('active');
    }

    function commitDraft() {
        Object.assign(committedState, draftState);
        document.getElementById('pax_adult').value = committedState.adult;
        document.getElementById('pax_child').value = committedState.child;
        document.getElementById('pax_infant').value = committedState.infant;
        document.getElementById('pax_student').value = committedState.student;
        cabinInput.value = committedState.cabin;
        updateSummary();
        clearFormError();
        closePaxDropdown();
    }

    function cancelDraft() {
        closePaxDropdown();
    }

    function syncDraftToUI() {
        document.getElementById('display_adult').innerText = draftState.adult;
        document.getElementById('display_child').innerText = draftState.child;
        document.getElementById('display_infant').innerText = draftState.infant;
        document.getElementById('display_student').innerText = draftState.student;

        cabinRadios.forEach(radio => {
            radio.checked = (radio.value === draftState.cabin);
            radio.closest('.dh-cabin-tab').classList.toggle('active', radio.checked);
        });

        updateButtonStates();
    }

    function updateSummary() {
        const total = committedState.adult + committedState.child + committedState.infant + committedState.student;
        const cabinShort = committedState.cabin === 'economy' ? 'ECO' : 'BUS';
        summaryText.innerHTML =
            '<span class="dh-pax-main-text">' + total + ' Yolcu</span>' +
            '<span class="dh-pax-sub-text">' + cabinShort + '</span>';
    }

    function updateButtonStates() {
        const totalPax = draftState.adult + draftState.child + draftState.infant + draftState.student;
        paxButtons.forEach(btn => {
            const type = btn.getAttribute('data-type');
            const action = btn.getAttribute('data-action');
            if (action === 'plus') {
                btn.disabled = (totalPax >= MAX_TOTAL_PAX) || (type === 'infant' && draftState.infant >= (draftState.adult + draftState.student));
            } else {
                btn.disabled = (draftState[type] === 0) || ((type === 'adult' || type === 'student') && draftState.adult + draftState.student === 1 && draftState[type] === 1);
            }
        });
    }

    passengerToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (passengerDropdown.hidden) {
            openPaxDropdown();
        } else {
            cancelDraft();
        }
    });

    // Yolcu dropdown'unun dış tıklaması — vazgeç
    document.addEventListener('click', function (e) {
        if (!passengerWrapper.contains(e.target) && !passengerDropdown.hidden) {
            cancelDraft();
        }
    });

    cabinRadios.forEach(radio => {
        radio.addEventListener('change', function () {
            draftState.cabin = this.value;
            document.querySelectorAll('.dh-cabin-tab').forEach(tab => tab.classList.remove('active'));
            this.closest('.dh-cabin-tab').classList.add('active');
        });
    });

    paxButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const type = btn.getAttribute('data-type');
            const action = btn.getAttribute('data-action');
            const totalPax = draftState.adult + draftState.child + draftState.infant + draftState.student;

            if (action === 'plus' && totalPax < MAX_TOTAL_PAX) {
                if (type === 'infant' && draftState.infant >= (draftState.adult + draftState.student)) return;
                draftState[type]++;
            } else if (action === 'minus' && draftState[type] > 0) {
                if ((type === 'adult' || type === 'student') && (draftState.adult + draftState.student) === 1) return;
                draftState[type]--;
                if ((type === 'adult' || type === 'student') && draftState.infant > (draftState.adult + draftState.student)) {
                    draftState.infant = draftState.adult + draftState.student;
                }
            }
            syncDraftToUI();
        });
    });

    paxApplyBtn.addEventListener('click', commitDraft);
    paxCancelBtn.addEventListener('click', cancelDraft);

    updateSummary();
});
