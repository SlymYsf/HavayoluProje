/**
 * passenger-form.js — Yolcu bilgileri sayfası (/yolcu-bilgileri)
 *
 *   - Doğum tarihi: Flatpickr + maskeli/sınırlı elle GG.AA.YYYY girişi
 *   - Belge tipi: T.C. Kimlik / Pasaport geçişi + id_type gizli alanının senkronu
 *   - Ülke kodu: aranabilir liste, dial code'un yanında ISO kodunu da forma yazar
 *   - Telefon: libphonenumber-js ile ülkeye özgü biçim ve doğrulama
 *
 * libphonenumber yüklü değilse telefon mantığı eski davranışa geri düşer.
 */
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // ================= YARDIMCILAR =================

    /** libphonenumber-js global'i — yoksa null. */
    function lib() {
        return (window.libphonenumber && window.libphonenumber.AsYouType)
            ? window.libphonenumber
            : null;
    }

    /**
     * GG.AA.YYYY metnini gerçek bir Date'e çevirir, geçersizse null döner.
     * Takvim kontrolü de yapar: 31.02.2000 gibi bir tarih Date tarafından
     * sessizce 02.03.2000'e kaydırılır, biz bunu reddediyoruz.
     */
    function parseDmy(value) {
        var m = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(String(value || '').trim());
        if (!m) return null;

        var day = +m[1], month = +m[2], year = +m[3];
        var dt = new Date(year, month - 1, day);

        if (dt.getFullYear() !== year || dt.getMonth() !== month - 1 || dt.getDate() !== day) {
            return null;
        }
        return dt;
    }

    /** Yazarken noktaları otomatik koyar, 8 rakamla sınırlar. */
    function maskDmy(value) {
        var d = String(value || '').replace(/\D/g, '').slice(0, 8);
        if (d.length > 4) return d.slice(0, 2) + '.' + d.slice(2, 4) + '.' + d.slice(4);
        if (d.length > 2) return d.slice(0, 2) + '.' + d.slice(2);
        return d;
    }

    // ================= ALAN BAZLI HATA GÖSTERİMİ =================
    // (fonksiyon bildirimi olarak yukarı taşındı — aşağıdaki bloklar bunu çağırıyor)

    function setFieldError(input, message) {
        var field = input.closest('.dh-field');
        if (!field) return;
        field.classList.add('dh-field-invalid');

        var msg = document.querySelector('.dh-field-msg[data-for="' + input.id + '"]');
        if (!msg) {
            msg = document.createElement('span');
            msg.className = 'dh-field-msg';
            msg.dataset.for = input.id;
            field.parentElement.insertBefore(msg, field.nextSibling);

            // Grid'de mesaj bir sonraki boş hücreye akıyor; hatalı alanın sütununa sabitliyoruz
            var grid = field.parentElement;
            if (grid.classList.contains('dh-pax-grid')) {
                var fr = field.getBoundingClientRect();
                var gr = grid.getBoundingClientRect();
                msg.style.gridColumn = (fr.left - gr.left) > gr.width / 2 ? '2' : '1';
            }
        }
        msg.textContent = message;
    }

    function clearFieldError(input) {
        var field = input.closest('.dh-field');
        if (field) field.classList.remove('dh-field-invalid');

        var msg = document.querySelector('.dh-field-msg[data-for="' + input.id + '"]');
        if (msg) msg.remove();
    }

    // ================= DOĞUM TARİHİ =================

    if (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.tr) {
        flatpickr.localize(flatpickr.l10ns.tr);
    }

    // KRİTİK: minDate/maxDate'e string verilirse Flatpickr onu dateFormat ('d.m.Y')
    // ile parse etmeye çalışır ve '1906-01-01' → 19.06.2026 olur. Date nesnesi
    // verildiğinde parse adımı hiç çalışmaz.
    var today = new Date();
    var minBirth = new Date(today.getFullYear() - 120, 0, 1);
    var maxBirth = new Date(today.getFullYear(), today.getMonth(), today.getDate());

    document.querySelectorAll('.js-birth-date').forEach(function (input) {
        // Sunucudan bozuk bir old() değeri geldiyse Flatpickr'a hiç verme
        if (input.value && !parseDmy(input.value)) input.value = '';

        var fp = window.flatpickr ? flatpickr(input, {
            dateFormat: 'd.m.Y',
            allowInput: true,
            minDate: minBirth,
            maxDate: maxBirth,
            disableMobile: true,
            // Flatpickr'ın kendi ayrıştırıcısı takvimde olmayan tarihi kaydırıyor.
            // Katı ayrıştırıcımızı veriyoruz: geçersizse undefined döner ve
            // Flatpickr tarihi seçili saymaz.
            parseDate: function (datestr) {
                return parseDmy(datestr) || undefined;
            },
            errorHandler: function () {}, // geçersiz girişte konsolu kirletme
            onChange: function (selectedDates) {
                if (selectedDates.length) {
                    delete input.dataset.typed;
                    clearFieldError(input);
                }
            }
        }) : null;

        input.addEventListener('input', function (e) {
            var masked = maskDmy(input.value);
            if (input.value !== masked) input.value = masked;

            // Flatpickr geçersiz metni temizlerken de 'input' tetikleniyor ve
            // yazdığımız metni eziyordu. Sentetik olaylarda isTrusted false gelir.
            if (e.isTrusted) {
                if (input.value) {
                    input.dataset.typed = input.value;
                } else {
                    delete input.dataset.typed;
                }
            }

            clearFieldError(input);
        });

        // Enter formu göndermesin; önce tarihi işle
        input.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            if (validateBirthDate(input) && fp) {
                fp.setDate(parseDmy(input.value), false);
                fp.close();
            }
        });

        input.addEventListener('blur', function () {
            // Takvimden seçim yapılırken blur tetiklenebilir, kısa gecikme sahte hatayı önler
            setTimeout(function () { validateBirthDate(input); }, 150);
        });
    });

    function validateBirthDate(input) {

        var typed = input.dataset.typed;

        // Alanda kaydırılmış tarih duruyor olabilir; kullanıcının yazdığını doğruluyoruz
        if (typed && typed.length === 10 && !parseDmy(typed)) {
            input.value = typed;
            setFieldError(input, 'Lütfen geçerli bir tarih giriniz.');
            return false;
        }

        var value = input.value.trim();
        if (!value) return true; // boş alan kuralı 'required' ile yakalanıyor

        var dt = parseDmy(value);
        if (!dt) {
            setFieldError(input, 'Tarihi GG.AA.YYYY biçiminde girin (örn. 15.03.1998).');
            return false;
        }
        if (dt > maxBirth) {
            setFieldError(input, 'Doğum tarihi bugünden ileri olamaz.');
            return false;
        }
        if (dt < minBirth) {
            setFieldError(input, 'Geçerli bir doğum tarihi girin.');
            return false;
        }

        clearFieldError(input);
        return true;
    }

    // ================= BELGE TİPİ =================

    document.querySelectorAll('.js-doc-foreign').forEach(function (checkbox) {
        var index = checkbox.dataset.index;
        var input = document.querySelector('.js-doc-input[data-index="' + index + '"]');
        var typeField = document.querySelector('.js-doc-type[data-index="' + index + '"]');
        var label = document.querySelector('label[for="p' + index + '-id"]');

        if (!input || !typeField) return;

        function applyDocType(resetValue) {
            var isForeign = checkbox.checked;

            // Sunucunun beklediği alan bu — checkbox değil
            typeField.value = isForeign ? 'passport' : 'tc';

            input.maxLength = isForeign ? 9 : 11;
            input.placeholder = isForeign ? 'U1234567' : '12345678901';
            input.setAttribute('inputmode', isForeign ? 'text' : 'numeric');
            if (label) label.textContent = isForeign ? 'Pasaport No' : 'T.C. Kimlik No';
            if (resetValue) input.value = '';
            clearFieldError(input);
        }

        checkbox.addEventListener('change', function () {
            applyDocType(true);
            input.focus();
        });

        input.addEventListener('input', function () {
            if (checkbox.checked) {
                input.value = input.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, 20);
            } else {
                input.value = input.value.replace(/\D/g, '').slice(0, 11);
            }
            clearFieldError(input);
        });

        input.addEventListener('blur', function () {
            validateDocument(checkbox, input);
        });

        applyDocType(false); // açılışta eski girdiyi koru
    });

    /** T.C. Kimlik No algoritması (11 hane, ilk hane 0 olamaz, 10. ve 11. hane kontrol basamağı). */
    function isValidTcNo(value) {
        if (!/^[1-9][0-9]{10}$/.test(value)) return false;

        var d = value.split('').map(Number);
        var odd = d[0] + d[2] + d[4] + d[6] + d[8];
        var even = d[1] + d[3] + d[5] + d[7];

        var tenth = ((odd * 7) - even) % 10;
        if (tenth < 0) tenth += 10;
        if (tenth !== d[9]) return false;

        var sumFirstTen = d.slice(0, 10).reduce(function (a, b) { return a + b; }, 0);
        return (sumFirstTen % 10) === d[10];
    }

    function validateDocument(checkbox, input) {
        var value = input.value.trim();
        if (!value) return true;

        if (checkbox.checked) {
            // ICAO Doc 9303, makinece okunabilir alanda belge numarasına 9 karakter ayırır.
            // Pratikte 6-9 karakter tüm yaygın ülkeleri kapsıyor.
            if (!/^[A-Z0-9]{6,9}$/.test(value)) {
                setFieldError(input, 'Pasaport numarası 6-9 karakter olmalı, yalnızca harf ve rakam içermelidir.');
                return false;
            }
            if (!/[0-9]/.test(value)) {
                setFieldError(input, 'Pasaport numarası en az bir rakam içermelidir.');
                return false;
            }
        } else {
            if (value.length !== 11) {
                setFieldError(input, 'T.C. Kimlik No 11 haneli olmalıdır.');
                return false;
            }
            if (!isValidTcNo(value)) {
                setFieldError(input, 'Geçersiz T.C. Kimlik No.');
                return false;
            }
        }

        clearFieldError(input);
        return true;
    }

    // ================= ÜLKE KODU =================

    var dialHidden = document.getElementById('contact-dial');
    var isoHidden = document.getElementById('contact-iso');
    var dialTrigger = document.getElementById('dial-trigger');
    var dialPanel = document.getElementById('dial-panel');
    var dialSearch = document.getElementById('dial-search');
    var dialList = document.getElementById('dial-list');
    var dialSelected = document.getElementById('dial-selected');
    var phoneInput = document.getElementById('contact-phone');

    var countries = [];
    var activeCountry = null;

    /** libphonenumber ISO koduyla çalışır, dial code'la değil (+1 → US/CA/PR ayırt edilemez). */
    function currentIso() {
        return (activeCountry && activeCountry.code) ? activeCountry.code : 'TR';
    }

    function flagHtml(country) {
        return country.flag
            ? '<img class="dh-dial-flag" src="' + country.flag + '" alt="" loading="lazy">'
            : '<span class="dh-dial-flag dh-dial-flag-empty"></span>';
    }

    function renderSelected(country) {
        activeCountry = country;
        dialHidden.value = country.dial;
        if (isoHidden) isoHidden.value = country.code;
        dialSelected.innerHTML = flagHtml(country) + '<span class="dh-dial-code">' + country.dial + '</span>';

        applyPhonePlaceholder();
        formatPhone();
        if (phoneInput) clearFieldError(phoneInput);
    }

    function renderList(query) {
        query = (query || '').trim().toLocaleLowerCase('tr');
        dialList.innerHTML = '';

        var matches = countries.filter(function (c) {
            if (!query) return true;
            return c.name.toLocaleLowerCase('tr').indexOf(query) !== -1 || c.dial.indexOf(query) !== -1;
        });

        if (!matches.length) {
            dialList.innerHTML = '<div class="dh-dial-empty">Sonuç bulunamadı.</div>';
            return;
        }

        matches.slice(0, 300).forEach(function (c) {
            var item = document.createElement('button');
            item.type = 'button';
            item.className = 'dh-dial-item' + (activeCountry && activeCountry.code === c.code ? ' dh-dial-item-active' : '');
            item.setAttribute('role', 'option');
            item.innerHTML = flagHtml(c) +
                '<span class="dh-dial-name">' + c.name + '</span>' +
                '<span class="dh-dial-item-code">(' + c.dial + ')</span>';
            item.addEventListener('click', function () {
                renderSelected(c);
                closeDialPanel();
                phoneInput.focus();
            });
            dialList.appendChild(item);
        });
    }

    function openDialPanel() {
        dialPanel.hidden = false;
        dialTrigger.setAttribute('aria-expanded', 'true');
        dialSearch.value = '';
        renderList('');
        dialSearch.focus();
    }

    function closeDialPanel() {
        dialPanel.hidden = true;
        dialTrigger.setAttribute('aria-expanded', 'false');
    }

    if (dialTrigger) {
        dialTrigger.addEventListener('click', function (e) {
            e.stopPropagation();
            if (dialPanel.hidden) { openDialPanel(); } else { closeDialPanel(); }
        });

        dialSearch.addEventListener('input', function () {
            renderList(dialSearch.value);
        });

        document.addEventListener('click', function (e) {
            if (!dialPanel.hidden && !dialPanel.contains(e.target) && !dialTrigger.contains(e.target)) {
                closeDialPanel();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !dialPanel.hidden) closeDialPanel();
        });

        fetch('/api/country-codes')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                countries = data;

                var savedIso = isoHidden ? isoHidden.value : '';
                var savedDial = dialHidden.value || '+90';

                var match = null;
                if (savedIso) {
                    match = countries.find(function (c) { return c.code === savedIso; });
                }
                if (!match && savedDial !== '+90') {
                    match = countries.find(function (c) { return c.dial === savedDial; });
                }
                if (!match) {
                    match = countries.find(function (c) { return c.code === 'TR'; });
                }
                if (match) renderSelected(match);
            })
            .catch(function () {
                dialList.innerHTML = '<div class="dh-dial-empty">Ülke listesi yüklenemedi.</div>';
            });
    }

    // ================= TELEFON =================

    function applyPhonePlaceholder() {
        if (!phoneInput) return;
        phoneInput.placeholder = currentIso() === 'TR' ? '5553869777' : 'Telefon numarası';
    }


    var planCache = {};

    /**
     * Ülkenin numara planı: { max: en uzun ulusal hane sayısı, prefix: şehirlerarası önek }.
     * Metadata okunamazsa null döner.
     */
    function numberingPlan(iso) {
        if (planCache.hasOwnProperty(iso)) return planCache[iso];

        var L = lib();
        var result = null;

        if (L && L.Metadata) {
            try {
                var meta = new L.Metadata();
                meta.selectNumberingPlan(iso);

                var lengths = meta.numberingPlan.possibleLengths();
                if (lengths && lengths.length) {
                    var prefix = '';
                    if (typeof meta.numberingPlan.nationalPrefix === 'function') {
                        prefix = meta.numberingPlan.nationalPrefix() || '';
                    }
                    result = { max: lengths[lengths.length - 1], prefix: prefix };
                }
            } catch (e) {
                result = null;
            }
        }

        planCache[iso] = result;
        return result;
    }

    /** Kullanıcı baştaki 0'ı yazdıysa at — uluslararası biçimde yeri yok. */
    function stripNationalPrefix(digits, iso) {
        var plan = numberingPlan(iso);
        if (plan && plan.prefix && digits.length > plan.max && digits.indexOf(plan.prefix) === 0) {
            return digits.slice(plan.prefix.length);
        }
        return digits;
    }

    /**
     * Ülkeye göre azami hane sayısına indirir.
     * validatePhoneNumberLength'in dönüş metnine güvenmiyoruz — anlamsız
     * dizilerde 'TOO_LONG' yerine başka bir değer dönebiliyor.
     */
    function capDigits(digits, iso) {
        digits = digits.slice(0, 15); // E.164 mutlak üst sınır

        var plan = numberingPlan(iso);
        if (plan && plan.max) return digits.slice(0, plan.max);

        return iso === 'TR' ? digits.slice(0, 10) : digits;
    }

    /**
     * Numarayı düz rakam olarak tutar — gruplama/boşluk yok (THY davranışı).
     * Yalnızca ülkeye özgü azami hane sınırı uygulanır.
     */
    function formatPhone() {
        if (!phoneInput) return;

        var iso = currentIso();
        var digits = phoneInput.value.replace(/\D/g, '');

        phoneInput.value = capDigits(stripNationalPrefix(digits, iso), iso);
        phoneInput.maxLength = 15;
    }

    function validatePhone(showError) {
        if (!phoneInput) return true;

        var value = phoneInput.value.trim();
        var digits = value.replace(/\D/g, '');
        if (!digits) return true; // 'required' ayrı yakalıyor

        var L = lib();
        var iso = currentIso();
        var ok;

        if (L && L.isValidPhoneNumber) {
            ok = L.isValidPhoneNumber(value, iso);

            // min bundle'da tip bilgisi yok, getType() kullanılamıyor.
            // Baskın senaryo olan TR için cep şartını elle uyguluyoruz;
            // diğer ülkelerde sabit hat sunucuda yakalanır.
            if (ok && iso === 'TR' && digits.slice(-10).charAt(0) !== '5') {
                ok = false;
            }
        } else {
            ok = iso === 'TR'
                ? (digits.length === 10 && digits.charAt(0) === '5')
                : digits.length >= 7;
        }

        if (!ok) {
            if (showError) {
                var name = activeCountry ? activeCountry.name : 'seçili ülke';
                setFieldError(phoneInput, name + ' için geçerli bir cep telefonu numarası girin.');
            }
            return false;
        }

        clearFieldError(phoneInput);
        return true;
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function () {
            formatPhone();
            clearFieldError(phoneInput);
        });

        phoneInput.addEventListener('blur', function () {
            validatePhone(true);
        });

        applyPhonePlaceholder();
        formatPhone();
    }

    // ================= FORM GÖNDERİMİ =================

    var form = document.getElementById('passenger-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            var valid = true;

            document.querySelectorAll('.js-doc-foreign').forEach(function (checkbox) {
                var input = document.querySelector('.js-doc-input[data-index="' + checkbox.dataset.index + '"]');
                if (input && !validateDocument(checkbox, input)) valid = false;
            });

            document.querySelectorAll('.js-birth-date').forEach(function (input) {
                if (!validateBirthDate(input)) valid = false;
            });

            if (!validatePhone(true)) valid = false;

            if (!valid) {
                e.preventDefault();
                var firstError = document.querySelector('.dh-field-invalid');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }
});
