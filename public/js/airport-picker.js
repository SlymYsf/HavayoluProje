/**
 * airport-picker.js — Yeniden kullanılabilir havalimanı seçici.
 *
 * Arama formundaki seçicinin (search-form.js) davranışını bağımsız bir
 * bileşene taşır: yazarak arama, şehir bazlı gruplama ve "tüm uçuş
 * noktaları" modalı. search-form.js'e dokunulmadı; o dosya kendi
 * mantığıyla çalışmaya devam ediyor.
 *
 * Modal DOM'da hazır beklemiyor, ilk ihtiyaç anında üretiliyor — böylece
 * mevcut destinations-modal ile kimlik çakışması olmuyor.
 */
window.DHAirportPicker = (function () {
    'use strict';

    var airportsPromise = null;
    var modal = null;
    var activePicker = null;

    /** Havalimanı listesi bir kez çekilir, tüm seçiciler paylaşır. */
    function loadAirports() {
        if (!airportsPromise) {
            airportsPromise = fetch('/api/airports')
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    return Array.isArray(data) ? data : (data.data || []);
                });
        }
        return airportsPromise;
    }

    /** Havalimanlarını şehir bazlı gruplar; çok havalimanlı şehirlere "Tümü" girdisi ekler. */
    function buildItems(airports) {
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

    /** Kelime başlangıcı eşleşmesi: "ist" → İstanbul, ama "sta" → Stansted değil. */
    function matchesWordStart(text, query) {
        if (!text) return false;

        var words = text.toLocaleLowerCase('tr').split(/[\s\-\/]+/);

        for (var i = 0; i < words.length; i++) {
            if (words[i].indexOf(query) === 0) return true;
        }
        return false;
    }

    function esc(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    // ================= MODAL =================

    function ensureModal() {
        if (modal) return modal;

        var el = document.createElement('div');
        el.className = 'dh-modal';
        el.hidden = true;
        el.innerHTML =
            '<div class="dh-modal-backdrop"></div>' +
            '<div class="dh-modal-box">' +
            '<div class="dh-modal-header">' +
            '<span>Aşağıdaki ülke ve şehirler arasından seçim yapabilirsiniz.</span>' +
            '<button type="button" class="dh-modal-close" aria-label="Kapat">' +
            '<i class="ti ti-x" aria-hidden="true"></i></button>' +
            '</div>' +
            '<div class="dh-modal-body">' +
            '<div class="dh-modal-col">' +
            '<div class="dh-modal-col-title">' +
            '<i class="ti ti-world" aria-hidden="true"></i> Ülke / Bölge (<span class="js-country-count">0</span>)' +
            '</div>' +
            '<div class="dh-modal-list js-country-list"></div>' +
            '</div>' +
            '<div class="dh-modal-col">' +
            '<div class="dh-modal-col-title">' +
            '<i class="ti ti-plane" aria-hidden="true"></i> Havalimanı (<span class="js-airport-count">0</span>)' +
            '</div>' +
            '<div class="dh-modal-list js-airport-list"></div>' +
            '</div>' +
            '</div>' +
            '</div>';

        document.body.appendChild(el);

        el.querySelector('.dh-modal-close').addEventListener('click', closeModal);
        el.querySelector('.dh-modal-backdrop').addEventListener('click', closeModal);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !el.hidden) closeModal();
        });

        modal = el;
        return modal;
    }

    function openModal(picker) {
        activePicker = picker;

        var m = ensureModal();
        buildCountryList(picker.items);

        m.hidden = false;
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        if (!modal) return;

        modal.hidden = true;
        document.body.style.overflow = '';
        activePicker = null;
    }

    function buildCountryList(items) {
        var countries = [];

        items.forEach(function (item) {
            if (item.type === 'airport' && countries.indexOf(item.country) === -1) {
                countries.push(item.country);
            }
        });

        countries.sort(function (a, b) { return a.localeCompare(b, 'tr'); });

        var list = modal.querySelector('.js-country-list');
        modal.querySelector('.js-country-count').textContent = countries.length;
        list.innerHTML = '';

        var currentLetter = '';

        countries.forEach(function (country) {
            var letter = country.charAt(0).toLocaleUpperCase('tr');

            if (letter !== currentLetter) {
                currentLetter = letter;
                var header = document.createElement('div');
                header.className = 'dh-modal-letter';
                header.textContent = letter;
                list.appendChild(header);
            }

            var item = document.createElement('div');
            item.className = 'dh-modal-item';
            item.textContent = country;
            item.addEventListener('click', function () {
                list.querySelectorAll('.dh-modal-item').forEach(function (el) {
                    el.classList.remove('dh-modal-item-active');
                });
                item.classList.add('dh-modal-item-active');
                showAirportsOf(country, items);
            });

            list.appendChild(item);
        });

        modal.querySelector('.js-airport-list').innerHTML =
            '<div class="dh-modal-hint">Soldan bir ülke seçin.</div>';
    }

    function showAirportsOf(country, items) {
        var matches = items.filter(function (i) { return i.country === country; });
        var list = modal.querySelector('.js-airport-list');

        modal.querySelector('.js-airport-count').textContent =
            matches.filter(function (i) { return i.type === 'airport'; }).length;

        list.innerHTML = '';

        matches.forEach(function (item) {
            var el = document.createElement('div');
            el.className = 'dh-modal-item';
            var icon = item.type === 'city' ? 'ti-world' : 'ti-plane';
            el.innerHTML = '<i class="ti ' + icon + ' dh-autocomplete-icon" aria-hidden="true"></i>' + esc(item.label);

            el.addEventListener('click', function () {
                if (activePicker) activePicker.select(item);
                closeModal();
            });

            list.appendChild(el);
        });
    }

    // ================= SEÇİCİ =================

    /**
     * @param {object} opts
     *   input     — metin kutusu
     *   hidden    — seçilen kimliklerin yazılacağı gizli alan
     *   dropdown  — öneri listesi kabı
     *   onSelect  — (isteğe bağlı) seçim sonrası çağrılır
     */
    function attach(opts) {
        var input = opts.input;
        var hidden = opts.hidden;
        var dropdown = opts.dropdown;
        var container = input.closest('.dh-route-half');

        var picker = {
            items: [],
            selected: null,
            select: select
        };

        loadAirports().then(function (airports) {
            picker.items = buildItems(airports);
        });

        input.addEventListener('focus', function () { render(input.value); });
        input.addEventListener('input', function () {
            hidden.value = '';
            clearDisplay();
            render(input.value);
        });

        document.addEventListener('click', function (e) {
            if (!container.contains(e.target)) dropdown.hidden = true;
        });

        function render(query) {
            dropdown.innerHTML = '';
            query = query.trim().toLocaleLowerCase('tr');

            if (query === '') {
                var allBtn = document.createElement('div');
                allBtn.className = 'dh-autocomplete-all';
                allBtn.innerHTML = '<i class="ti ti-world" aria-hidden="true"></i> Tüm uçuş noktalarını gör';
                allBtn.addEventListener('click', function () {
                    dropdown.hidden = true;
                    openModal(picker);
                });
                dropdown.appendChild(allBtn);
                dropdown.hidden = false;
                return;
            }

            var matches = picker.items.filter(function (item) {
                return matchesWordStart(item.name, query)
                    || matchesWordStart(item.city, query)
                    || matchesWordStart(item.country, query);
            });

            if (!matches.length) {
                var empty = document.createElement('div');
                empty.className = 'dh-autocomplete-empty';
                empty.textContent = 'Eşleşen uçuş noktası bulunamadı.';
                dropdown.appendChild(empty);
                dropdown.hidden = false;
                return;
            }

            matches.slice(0, 50).forEach(function (item) {
                var el = document.createElement('div');
                el.className = 'dh-autocomplete-item';
                var icon = item.type === 'city' ? 'ti-world' : 'ti-plane';
                el.innerHTML =
                    '<div class="dh-autocomplete-city">' +
                    '<i class="ti ' + icon + ' dh-autocomplete-icon" aria-hidden="true"></i>' + esc(item.label) +
                    '</div>' +
                    '<div class="dh-autocomplete-meta">' + esc(item.city) + ', ' + esc(item.country) + '</div>';

                el.addEventListener('click', function () {
                    select(item);
                    dropdown.hidden = true;
                });

                dropdown.appendChild(el);
            });

            dropdown.hidden = false;
        }

        function select(item) {
            hidden.value = item.ids.join(',');
            input.value = item.label;
            picker.selected = item;
            renderDisplay(item);

            if (opts.onSelect) opts.onSelect(item);
        }

        /** Seçim sonrası iki satırlı görünüm: şehir üstte, havalimanı altta. */
        function renderDisplay(item) {
            clearDisplay();

            var display = document.createElement('div');
            display.className = 'dh-route-selected';
            display.innerHTML =
                '<span class="dh-route-city">' + esc(item.displayCity) + '</span>' +
                '<span class="dh-route-subtitle">' + esc(item.displaySubtitle) + '</span>';

            display.addEventListener('click', function (e) {
                e.stopPropagation();
                clearDisplay();
                hidden.value = '';
                input.value = '';
                input.focus();
                render('');
            });

            container.appendChild(display);
            input.classList.add('dh-route-input-hidden');
        }

        function clearDisplay() {
            var existing = container.querySelector('.dh-route-selected');
            if (existing) existing.remove();

            input.classList.remove('dh-route-input-hidden');
            picker.selected = null;
        }

        picker.reset = function () {
            clearDisplay();
            hidden.value = '';
            input.value = '';
        };

        return picker;
    }

    return { attach: attach, loadAirports: loadAirports };
})();
