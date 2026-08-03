/**
 * announcements.js — Header'daki bildirim çanı.
 *
 * Duyurular çana ilk tıklandığında çekiliyor; her sayfa açılışında istek
 * atmak gereksiz yük oluşturuyordu. Rozetteki sayı ise sayfa açılışında
 * hafif bir istekle alınıyor.
 *
 * Okundu bilgisi localStorage'da tutuluyor: üyelik sistemi olmadığı için
 * sunucuda kişi bazlı saklanacak yer yok. Üyelik geldiğinde bu durum
 * kullanıcıya bağlı bir tabloya taşınmalı.
 */
(function () {
    var bell = document.getElementById('announcement-bell');
    var trigger = document.getElementById('announcement-trigger');
    var panel = document.getElementById('announcement-panel');
    var list = document.getElementById('announcement-list');
    var badge = document.getElementById('announcement-count');
    var closeBtn = document.getElementById('announcement-close');
    var tabsBox = document.getElementById('announcement-tabs');
    var readAllBtn = document.getElementById('announcement-readall');
    if (!bell || !trigger || !panel || !list) return;

    var STORAGE_KEY = 'dh_read_announcements';
    var t = window.dhT || function (key) { return key; };
    var locale = window.dhLocale === 'en' ? 'en-GB' : 'tr-TR';

    var items = [];
    var activeFilter = 'all';
    var loaded = false;

    var TYPE_LABELS = {
        delay: t('Uçuş duyurusu'),
        cancellation: t('Uçuş duyurusu'),
        general: t('Genel duyuru')
    };

    /** Sekme filtreleri duyuru türlerine eşleniyor. */
    function matchesFilter(item, filter) {
        if (filter === 'all') return true;
        if (filter === 'flight') return item.type === 'delay' || item.type === 'cancellation';

        return item.type === 'general';
    }

    // ================= OKUNDU DURUMU =================

    function readIds() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
        } catch (e) {
            return [];
        }
    }

    function saveReadIds(ids) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
        } catch (e) {
            // Depolama kapalıysa okundu durumu tutulmaz, uygulama çalışmaya devam eder.
        }
    }

    function isRead(item) {
        return readIds().indexOf(item.id) !== -1;
    }

    function markRead(id) {
        var ids = readIds();

        if (ids.indexOf(id) === -1) {
            ids.push(id);
            saveReadIds(ids);
        }
    }

    function markAllRead() {
        saveReadIds(items.map(function (item) { return item.id; }));
        render();
        updateBadge();
    }

    // ================= GÖRÜNÜM =================

    function esc(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    /** Saat ve tarih ayrı ayrı; duyurular hem tazeliğiyle hem tarihiyle anlamlı. */
    function formatStamp(iso) {
        var d = new Date(iso);

        var time = String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        var date = d.toLocaleDateString(locale, { year: 'numeric', month: '2-digit', day: '2-digit' });

        return time + ' | ' + date;
    }

    function render() {
        var visible = items.filter(function (item) {
            return matchesFilter(item, activeFilter);
        });

        if (!visible.length) {
            list.innerHTML = '<p class="dh-bell-empty">' + esc(list.dataset.emptyText) + '</p>';
            return;
        }

        var logo = list.dataset.logo;

        list.innerHTML = visible.map(function (item) {
            var unread = !isRead(item);

            return '<article class="dh-bell-item' + (unread ? ' dh-bell-item-unread' : '') + '" data-id="' + item.id + '">' +
                '<img class="dh-bell-item-logo" src="' + esc(logo) + '" alt="">' +
                '<div class="dh-bell-item-main">' +
                '<p class="dh-bell-item-title">' + esc(item.title) + '</p>' +
                '<p class="dh-bell-item-meta">' + esc(formatStamp(item.published_at)) +
                ' &nbsp;|&nbsp; ' + esc(TYPE_LABELS[item.type] || TYPE_LABELS.general) + '</p>' +
                '<p class="dh-bell-item-body">' + esc(item.body) + '</p>' +
                '</div>' +
                (unread ? '<span class="dh-bell-dot" aria-hidden="true"></span>' : '') +
                '</article>';
        }).join('');

        list.querySelectorAll('.dh-bell-item').forEach(function (el) {
            el.addEventListener('click', function () {
                markRead(parseInt(el.dataset.id, 10));
                el.classList.remove('dh-bell-item-unread');

                var dot = el.querySelector('.dh-bell-dot');
                if (dot) dot.remove();

                updateBadge();
            });
        });
    }

    function renderTabCounts() {
        if (!tabsBox) return;

        tabsBox.querySelectorAll('.dh-bell-tab').forEach(function (tab) {
            var count = items.filter(function (item) {
                return matchesFilter(item, tab.dataset.filter);
            }).length;

            var counter = tab.querySelector('.dh-bell-tab-count');
            if (counter) counter.textContent = count;
        });
    }

    /** Rozet toplam sayıyı değil, okunmamış sayısını gösteriyor. */
    function updateBadge() {
        if (!badge) return;

        var unread = items.filter(function (item) { return !isRead(item); }).length;

        badge.textContent = unread > 9 ? '9+' : unread;
        badge.hidden = unread === 0;
    }

    // ================= VERİ =================

    function fetchAnnouncements() {
        return fetch('/api/announcements').then(function (res) { return res.json(); });
    }

    function load() {
        list.innerHTML = '<p class="dh-bell-empty">' + esc(list.dataset.loadingText) + '</p>';

        fetchAnnouncements()
            .then(function (data) {
                loaded = true;
                items = data.announcements || [];
                render();
                renderTabCounts();
                updateBadge();
            })
            .catch(function () {
                list.innerHTML = '<p class="dh-bell-empty">' + esc(list.dataset.errorText) + '</p>';
            });
    }

    // ================= AÇ / KAPA =================

    function open() {
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');

        // Liste sayfa açılışında alınmış olabilir; o durumda yeniden istek
        // atmadan doğrudan çiziliyor.
        if (loaded) { render(); } else { load(); }
    }

    function close() {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    }

    trigger.addEventListener('click', function (event) {
        event.preventDefault();
        panel.hidden ? open() : close();
    });

    if (closeBtn) closeBtn.addEventListener('click', close);
    if (readAllBtn) readAllBtn.addEventListener('click', markAllRead);

    if (tabsBox) {
        tabsBox.querySelectorAll('.dh-bell-tab').forEach(function (tab) {
            tab.addEventListener('click', function () {
                activeFilter = tab.dataset.filter;

                tabsBox.querySelectorAll('.dh-bell-tab').forEach(function (other) {
                    other.classList.toggle('dh-bell-tab-active', other === tab);
                });

                render();
            });
        });
    }

    document.addEventListener('click', function (event) {
        if (!panel.hidden && !bell.contains(event.target)) close();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            close();
            trigger.focus();
        }
    });

    // Rozet ve sekme sayaçları için sayfa açılışında bir kez listeyi al.
    fetchAnnouncements()
        .then(function (data) {
            items = data.announcements || [];
            loaded = true;
            renderTabCounts();
            updateBadge();
        })
        .catch(function () { /* rozet gösterilmez, sessiz geç */ });
})();
