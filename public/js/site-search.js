/**
 * site-search.js — Header üst şeridindeki site içi arama.
 *
 * Arama dizini ayrı bir listede tutulmaz; sayfadaki iki kaynaktan okunur:
 *   - Yardım içerikleri (#help-contents)
 *   - Mega menüdeki gerçek bağlantılar (adresi '#' olanlar atlanır)
 * Menüye yeni bir sayfa eklendiğinde arama onu kendiliğinden bulur.
 */
(function () {
    var overlay = document.getElementById('site-search');
    var input = document.getElementById('site-search-input');
    var results = document.getElementById('site-search-results');
    var closeBtn = document.getElementById('site-search-close');
    if (!overlay || !input || !results) return;

    var triggers = document.querySelectorAll('[data-open-search]');
    var index = [];
    var lastFocused = null;
    /* Sonuç bulunamadı metni JS içinde gömülü olamaz (__() burada çalışmaz);
       Blade tarafından data özniteliğiyle aktarılıyor. */
    var emptyText = overlay.dataset.emptyText || '';

    /* Türkçe'de büyük/küçük harf dönüşümü İ/i ve I/ı çiftleri yüzünden
       varsayılan davranışla bozuluyor; karşılaştırma öncesi normalleştiriliyor. */
    function normalize(text) {
        return text
            .replace(/İ/g, 'i').replace(/I/g, 'ı')
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function buildIndex() {
        index = [];

        document.querySelectorAll('#help-contents [data-help-id]').forEach(function (node) {
            var title = node.dataset.helpTitle || '';

            index.push({
                type: 'help',
                title: title,
                body: (node.textContent || '').replace(/\s+/g, ' ').trim(),
                node: node,
                haystack: normalize(title + ' ' + node.textContent)
            });
        });

        var seen = {};

        document.querySelectorAll('.dh-mega-link[href]').forEach(function (link) {
            var href = link.getAttribute('href');
            var title = (link.textContent || '').trim();
            if (!href || href === '#' || seen[href + title]) return;

            seen[href + title] = true;

            index.push({
                type: 'page',
                title: title,
                href: href,
                haystack: normalize(title)
            });
        });
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /* Eşleşen kelimeler <mark> ile sarılıyor. Metin önce kaçırılıyor,
       vurgulama bundan sonra ekleniyor; aksi halde içerikteki < > karakterleri
       etiket sanılırdı. */
    function highlight(text, words) {
        var safe = escapeHtml(text);
        var normalized = normalize(text);
        var ranges = [];

        words.forEach(function (word) {
            var from = 0;
            var at;

            while ((at = normalized.indexOf(word, from)) !== -1) {
                ranges.push([at, at + word.length]);
                from = at + word.length;
            }
        });

        if (!ranges.length) return safe;

        // normalize() uzunluğu değiştirebildiği için kaçırılmış metinde
        // konumlar kayabilir; bu yüzden vurgulama ham metin üzerinden yapılıp
        // sonunda kaçırılıyor.
        ranges.sort(function (a, b) { return a[0] - b[0]; });

        var merged = [ranges[0]];
        ranges.slice(1).forEach(function (r) {
            var last = merged[merged.length - 1];
            if (r[0] <= last[1]) { last[1] = Math.max(last[1], r[1]); }
            else { merged.push(r); }
        });

        var out = '';
        var cursor = 0;

        merged.forEach(function (r) {
            out += escapeHtml(text.slice(cursor, r[0]));
            out += '<mark>' + escapeHtml(text.slice(r[0], r[1])) + '</mark>';
            cursor = r[1];
        });

        return out + escapeHtml(text.slice(cursor));
    }

    /* Yardım metninin tamamı değil, aranan kelimenin geçtiği yerin çevresi
       gösteriliyor; uzun paragraflar sonuç listesini okunmaz hale getiriyordu. */
    function snippet(body, words) {
        var normalized = normalize(body);
        var at = -1;

        words.some(function (word) {
            at = normalized.indexOf(word);
            return at !== -1;
        });

        if (at === -1) return body.slice(0, 140) + (body.length > 140 ? '…' : '');

        var start = Math.max(at - 60, 0);
        var end = Math.min(at + 100, body.length);

        return (start > 0 ? '…' : '') + body.slice(start, end) + (end < body.length ? '…' : '');
    }

    /* Tek harflik aramalar neredeyse her metinde geçtiği için tüm dizini
        döndürüyor ve vurgulama okunmaz hale geliyordu. */
    var MIN_QUERY_LENGTH = 2;

    /** Sorgu kelimelerinin kaçı bir kelimenin başında geçiyor? */
    function startsScore(item, words) {
        var tokens = item.haystack.split(' ');

        return words.filter(function (word) {
            return tokens.some(function (token) { return token.indexOf(word) === 0; });
        }).length;
    }

    function render(query) {
        var normalized = normalize(query);
        var words = normalized.split(' ').filter(Boolean);

        if (!words.length || normalized.length < MIN_QUERY_LENGTH) {
            results.innerHTML = '';
            return;
        }

        // Tüm kelimelerin geçmesi aranıyor; biri bile yoksa sonuç elenir.
        var matches = index.filter(function (item) {
            return words.every(function (word) {
                return item.haystack.indexOf(word) !== -1;
            });
        });

        if (!matches.length) {
            results.innerHTML = '<p class="dh-search-empty">' + escapeHtml(emptyText) + '</p>';
            return;
        }

        // Kelime başında eşleşen sonuçlar üste alınıyor: "bag" araması
        // "bagaj"ı "sorumluluğundadır" içindeki geçişten önce göstermeli.
        matches.sort(function (a, b) {
            return startsScore(b, words) - startsScore(a, words);
        });

        var shown = matches.slice(0, 8);

        /* data-hit indeksi 'shown' dizisine göre veriliyor: sıralama ve
           dilimlemeden sonra matches ile aynı sırada olmayabiliyordu ve
           yardım maddesine tıklayınca yanlış cevap açılıyordu. */
        results.innerHTML = shown.map(function (item, i) {
            if (item.type === 'page') {
                return '<a class="dh-search-hit" href="' + item.href + '">'
                    + '<i class="ti ti-arrow-right" aria-hidden="true"></i>'
                    + '<span class="dh-search-hit-title">' + highlight(item.title, words) + '</span>'
                    + '</a>';
            }

            return '<button type="button" class="dh-search-hit" data-hit="' + i + '">'
                + '<i class="ti ti-help-circle" aria-hidden="true"></i>'
                + '<span>'
                + '<span class="dh-search-hit-title">' + highlight(item.title, words) + '</span>'
                + '<span class="dh-search-hit-body">' + highlight(snippet(item.body, words), words) + '</span>'
                + '</span></button>';
        }).join('');

        results.querySelectorAll('[data-hit]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                expand(btn, shown[parseInt(btn.dataset.hit, 10)]);
            });
        });
    }

    /* Yardım cevabı yeni bir katman açmak yerine sonucun altında gösteriliyor;
       kullanıcı aramasını kaybetmeden cevabı görebiliyor. */
    function expand(button, item) {
        var open = button.nextElementSibling;

        if (open && open.classList.contains('dh-search-answer')) {
            open.remove();
            button.setAttribute('aria-expanded', 'false');
            return;
        }

        var box = document.createElement('div');
        box.className = 'dh-search-answer';
        box.innerHTML = item.node.innerHTML;

        button.setAttribute('aria-expanded', 'true');
        button.insertAdjacentElement('afterend', box);
    }

    function open() {
        lastFocused = document.activeElement;

        buildIndex();
        overlay.hidden = false;
        document.body.style.overflow = 'hidden';
        input.value = '';
        results.innerHTML = '';
        input.focus();
    }

    function close() {
        overlay.hidden = true;
        document.body.style.overflow = '';

        if (lastFocused) lastFocused.focus();
    }

    triggers.forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            open();
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', close);

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) close();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !overlay.hidden) close();
    });

    var timer = null;

    input.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () { render(input.value); }, 120);
    });
})();
