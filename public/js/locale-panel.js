/**
 * locale-panel.js — Dil ve bölge seçimi paneli.
 *
 * Panel içindeki iki seçici native <select> değil: bayrak gösterimi ve
 * liste yüksekliğinin sınırlanması native öğeyle mümkün olmadığı için
 * özel bir bileşen kullanılıyor. Seçilen değer gizli inputa yazılır,
 * kaydetme işlemi form gönderimiyle sunucuda yapılır.
 */
(function () {
    var trigger = document.getElementById('locale-trigger');
    var panel = document.getElementById('locale-panel');
    if (!trigger || !panel) return;

    /* Türkçe'de İ/i ve I/ı çiftleri varsayılan küçültmeyle bozuluyor. */
    function normalize(text) {
        return text.replace(/İ/g, 'i').replace(/I/g, 'ı').toLowerCase().trim();
    }

    function closePanel() {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        closeAllPickers();
    }

    var pickers = Array.prototype.slice.call(panel.querySelectorAll('.dh-picker'));

    function closeAllPickers(except) {
        pickers.forEach(function (picker) {
            if (picker === except) return;

            picker.querySelector('.dh-picker-panel').hidden = true;
            picker.querySelector('.dh-picker-trigger').setAttribute('aria-expanded', 'false');
        });
    }

    pickers.forEach(function (picker) {
        var pickerTrigger = picker.querySelector('.dh-picker-trigger');
        var pickerPanel = picker.querySelector('.dh-picker-panel');
        var hidden = picker.querySelector('input[type="hidden"]');
        var textEl = picker.querySelector('.dh-picker-text');
        var flagEl = picker.querySelector('.dh-picker-flag');
        var search = picker.querySelector('.dh-picker-search input');
        var items = Array.prototype.slice.call(picker.querySelectorAll('.dh-picker-item'));

        pickerTrigger.addEventListener('click', function () {
            var willOpen = pickerPanel.hidden;

            closeAllPickers(picker);
            pickerPanel.hidden = !willOpen;
            pickerTrigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

            if (willOpen && search) {
                search.value = '';
                items.forEach(function (item) { item.hidden = false; });
                search.focus();
            }
        });

        items.forEach(function (item) {
            item.addEventListener('click', function () {
                hidden.value = item.dataset.value;
                textEl.textContent = item.dataset.label;

                if (flagEl && item.dataset.flag) flagEl.src = item.dataset.flag;

                items.forEach(function (other) {
                    other.setAttribute('aria-selected', other === item ? 'true' : 'false');
                });

                pickerPanel.hidden = true;
                pickerTrigger.setAttribute('aria-expanded', 'false');
            });
        });

        if (search) {
            search.addEventListener('input', function () {
                var query = normalize(search.value);

                items.forEach(function (item) {
                    item.hidden = query !== '' && normalize(item.dataset.label).indexOf(query) === -1;
                });
            });
        }
    });

    trigger.addEventListener('click', function (event) {
        event.preventDefault();

        var willOpen = panel.hidden;
        panel.hidden = !willOpen;
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

        if (!willOpen) closeAllPickers();
    });

    document.addEventListener('click', function (event) {
        if (!panel.hidden && !event.target.closest('.dh-locale')) closePanel();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || panel.hidden) return;

        var openPicker = pickers.some(function (p) {
            return !p.querySelector('.dh-picker-panel').hidden;
        });

        // Önce açık seçici, sonra panelin kendisi kapanıyor.
        if (openPicker) { closeAllPickers(); } else { closePanel(); trigger.focus(); }
    });
})();
