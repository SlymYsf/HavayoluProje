document.addEventListener('DOMContentLoaded', function () {
    DHNavGuard.init();

    var params = new URLSearchParams(window.location.search);
    var resultsBox = document.getElementById('flight-results');

    var originIds = (params.get('origin') || '').split(',').filter(Boolean);
    var destinationIds = (params.get('destination') || '').split(',').filter(Boolean);
    var date = params.get('date') || '';
    var returnDate = params.get('return_date') || '';
    var isRoundTrip = params.get('trip_type') === 'round_trip';
    var cabin = params.get('cabin_class') || 'economy';
    var adult = params.get('adult') || '1';
    var child = params.get('child') || '0';
    var infant = params.get('infant') || '0';
    var student = params.get('student') || '0';

    if (!originIds.length || !destinationIds.length) {
        resultsBox.innerHTML = '<p class="dh-msg">Arama bilgileri eksik. Lütfen yeni bir arama yapın.</p>';
        return;
    }

    resultsBox.innerHTML = '<p class="dh-msg">Uçuşlar aranıyor...</p>';

    var outboundPromise = DHFlightRender.fetchFlights(originIds, destinationIds, date, cabin, adult, child, infant, student);

    if (isRoundTrip && returnDate) {
        var inboundPromise = DHFlightRender.fetchFlights(destinationIds, originIds, returnDate, cabin, adult, child, infant, student);
        Promise.all([outboundPromise, inboundPromise]).then(function (both) {
            resultsBox.innerHTML = '';
            DHFlightRender.appendSection(resultsBox, 'Gidiş uçuşları', both[0], 'outbound');
            DHFlightRender.appendSection(resultsBox, 'Dönüş uçuşları', both[1], 'inbound');
            initSelectionTracking(true);
        });
    } else {
        outboundPromise.then(function (outbound) {
            resultsBox.innerHTML = '';
            DHFlightRender.appendSection(resultsBox, 'Uçuşlar', outbound, 'outbound');
            initSelectionTracking(false);
        });
    }

    function initSelectionTracking(isRoundTrip) {
        var actionsBar = document.getElementById('results-actions');
        var priceEl = document.getElementById('selection-price');
        var hintEl = document.getElementById('selection-hint');
        var continueBtn = document.getElementById('continue-btn');

        if (!actionsBar) return;
        actionsBar.hidden = false;

        var selection = { outbound: null, inbound: null };

        // Kartlardan yayınlanan seçim event'ini yakala
        resultsBox.addEventListener('dh:fare-selected', function (e) {
            var card = e.detail.card;
            var section = card.dataset.section;

            // Aynı section'daki DİĞER kartlarda seçili state'i temizle
            var siblingCards = resultsBox.querySelectorAll('.dh-flight-card[data-section="' + section + '"]');
            siblingCards.forEach(function (sibling) {
                if (sibling !== card) {
                    sibling.querySelectorAll('.dh-fare-option').forEach(function (opt) {
                        opt.classList.remove('dh-fare-option-selected');
                    });
                }
            });

            selection[section] = {
                flightId: e.detail.flightId,
                cabin: e.detail.cabin,
                price: e.detail.price,
                flightNumber: e.detail.flightNumber
            };

            refreshActionsBar();
        });

        function refreshActionsBar() {
            var needsOutbound = !selection.outbound;
            var needsInbound = isRoundTrip && !selection.inbound;

            if (needsOutbound || needsInbound) {
                continueBtn.disabled = true;
                priceEl.textContent = '—';
                if (needsOutbound && needsInbound) {
                    hintEl.textContent = 'Gidiş ve dönüş için birer uçuş seçin';
                } else if (needsOutbound) {
                    hintEl.textContent = 'Gidiş uçuşu seçin';
                } else {
                    hintEl.textContent = 'Dönüş uçuşu seçin';
                }
                return;
            }

            continueBtn.disabled = false;
            var total = selection.outbound.price + (selection.inbound ? selection.inbound.price : 0);
            priceEl.textContent = Math.round(total).toLocaleString('tr-TR') + '₺';
            hintEl.textContent = isRoundTrip ? 'Gidiş + dönüş, tüm yolcular' : 'Tüm yolcular dahil';
        }

        continueBtn.addEventListener('click', function () {
            var p = new URLSearchParams();
            p.set('outbound_flight', selection.outbound.flightId);
            p.set('outbound_cabin', selection.outbound.cabin);
            if (selection.inbound) {
                p.set('inbound_flight', selection.inbound.flightId);
                p.set('inbound_cabin', selection.inbound.cabin);
            }
            p.set('adult', params.get('adult') || '1');
            p.set('child', params.get('child') || '0');
            p.set('infant', params.get('infant') || '0');
            p.set('student', params.get('student') || '0');

            DHNavGuard.release();
            window.location.href = '/yolcu-bilgileri?' + p.toString();
        });
    }
});
