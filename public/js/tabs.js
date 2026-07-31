/**
 * tabs.js — Ana sayfa sekme geçişleri.
 *
 * Ayrıca ?sekme=<ad> parametresini okur: header menüsündeki bağlantılar
 * ana sayfayı doğrudan ilgili sekme açık gelecek şekilde çağırır.
 */
(function () {
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.dh-tab'));
    if (!tabs.length) return;

    function activate(name) {
        tabs.forEach(function (t) {
            t.classList.toggle('dh-tab-active', t.dataset.tab === name);
        });

        document.querySelectorAll('.dh-panel').forEach(function (panel) {
            panel.hidden = panel.id !== 'panel-' + name;
        });
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            activate(tab.dataset.tab);
        });
    });

    // Geçersiz bir değer gelirse hiçbir şey yapılmıyor; aksi halde
    // tüm paneller gizlenir ve sayfa boş görünürdü.
    var wanted = new URLSearchParams(window.location.search).get('sekme');
    var exists = tabs.some(function (t) { return t.dataset.tab === wanted; });

    if (wanted && exists) activate(wanted);
})();
