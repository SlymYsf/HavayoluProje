document.querySelectorAll('.dh-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
        var target = tab.dataset.tab;

        document.querySelectorAll('.dh-tab').forEach(function (t) {
            t.classList.toggle('dh-tab-active', t === tab);
        });

        document.querySelectorAll('.dh-panel').forEach(function (panel) {
            panel.hidden = panel.id !== 'panel-' + target;
        });
    });
});
