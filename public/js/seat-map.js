/**
 * seat-map.js — Manuel koltuk seçimi.
 *
 * Kabin planı `GET /api/flights/{flight}/seat-map` uç noktasından çekiliyor;
 * sayfaya gömülmüyor çünkü dolu koltuk listesi sayfa açıldığı anda taze olmalı.
 * Yalnızca satın alınan kabin isteniyor: geniş gövdede 40+ sıralık tam planı
 * taşımak gereksiz, kullanıcı da zaten diğer kabinden koltuk seçemiyor.
 *
 * Ücretler burada yalnızca GÖSTERİLİYOR. Ödeme tutarı sunucuda, koltuk tipinden
 * yeniden hesaplanıyor (ReservationController::rebuildLegs) — buradaki sayıya
 * hiçbir yerde güvenilmiyor.
 */
(function () {
    'use strict';

    var ctx = window.DH_SEATS || { passengers: [], selected: {}, fareTotal: 0, hasInfant: false };

    /** direction => sunucudan gelen ham plan yanıtı */
    var legData = {};

    /** direction => { paxIndex: seat } */
    var selection = {};

    /** direction => aktif yolcu indeksi */
    var activePax = {};

    var CABIN_LABELS = {
        business: 'Business',
        economy: 'Economy'
    };

    var TYPE_LABELS = {
        standard: 'Standart koltuk',
        front_row: 'Ön sıra koltuk',
        extra_legroom: 'Ekstra diz mesafeli koltuk',
        exit: 'Acil çıkış koltuğu',
        bassinet: 'Bebek pusetli koltuk'
    };

    var EXIT_FORBIDDEN = ['child', 'infant'];

    function money(value) {
        return new Intl.NumberFormat('tr-TR', { maximumFractionDigits: 0 }).format(value) + '₺';
    }

    function passengerByIndex(index) {
        for (var i = 0; i < ctx.passengers.length; i++) {
            if (ctx.passengers[i].index === index) {
                return ctx.passengers[i];
            }
        }
        return null;
    }

    function seatPassengers() {
        return ctx.passengers.filter(function (p) { return p.needs_seat; });
    }

    /** Bu yolcu tipi bu koltuk tipine oturabilir mi? Sunucudaki kuralın aynısı. */
    function canOccupy(seatType, passengerType) {
        if (seatType === 'exit') {
            return EXIT_FORBIDDEN.indexOf(passengerType) === -1;
        }
        if (seatType === 'bassinet') {
            return ctx.hasInfant;
        }
        return true;
    }

    function occupancyError(seat, seatType) {
        if (seatType === 'exit') {
            return seat + ' acil çıkış koltuğudur; çocuk ve bebek yolcular bu koltuğa oturamaz.';
        }
        if (seatType === 'bassinet') {
            return seat + ' bebek pusetli koltuktur; yalnızca bebekli rezervasyonlara açıktır.';
        }
        return seat + ' koltuğu bu yolcu için seçilemez.';
    }

    // ---- Çizim -------------------------------------------------------------

    /** Tuvalet / mutfak birimi. */
    function serviceUnit(icon, label) {
        var unit = document.createElement('span');
        unit.className = 'dh-service-unit';
        unit.title = label;
        unit.innerHTML = '<i class="ti ' + icon + '" aria-hidden="true"></i>';
        return unit;
    }

    function serviceRow(units) {
        var row = document.createElement('div');
        row.className = 'dh-cabin-service';
        units.forEach(function (u) { row.appendChild(serviceUnit(u[0], u[1])); });
        return row;
    }

    function buildMap(container, payload) {
        var direction = container.dataset.direction;
        var cabins = payload.map.cabins;
        var occupied = payload.occupied_seats || [];
        var names = Object.keys(cabins);

        // Yalnızca satın alınan kabin çiziliyor. Burun ancak kabin 1. sıradan
        // başlıyorsa, kuyruk ancak economy çiziliyorsa gerçeğe uygun olur;
        // aksi halde gövde "kesik" gösteriliyor.
        var hasNose = names.length > 0 && cabins[names[0]].row_start === 1;
        var hasTail = names.indexOf('economy') !== -1;

        container.innerHTML = '';

        var shell = document.createElement('div');
        shell.className = 'dh-cabin-shell';

        if (hasNose) {
            var nose = document.createElement('div');
            nose.className = 'dh-fuselage-nose';
            nose.innerHTML = '<span class="dh-cockpit"><span></span><span></span></span>';
            shell.appendChild(nose);
        } else {
            shell.appendChild(cutEdge('top'));
        }

        var body = document.createElement('div');
        body.className = 'dh-cabin-body';

        // Burun ardı servis alanı: kokpitin arkasında tuvalet ve mutfak
        if (hasNose) {
            body.appendChild(serviceRow([
                ['ti-toilet-paper', 'Tuvalet'],
                ['ti-tools-kitchen-2', 'Mutfak']
            ]));
        }

        names.forEach(function (name) {
            var cabin = cabins[name];

            var deck = document.createElement('div');
            deck.className = 'dh-cabin-deck';
            deck.style.setProperty('--dh-seat-cols', cabin.seats_per_row);

            var title = document.createElement('div');
            title.className = 'dh-cabin-name';
            title.textContent = CABIN_LABELS[name] || name;
            deck.appendChild(title);

            var header = document.createElement('div');
            header.className = 'dh-cabin-letters';
            header.appendChild(document.createElement('span'));
            cabin.pattern.forEach(function (blockSize, blockIndex) {
                var block = document.createElement('div');
                block.className = 'dh-letter-block';
                for (var i = 0; i < blockSize; i++) {
                    var letterIndex = cabin.pattern.slice(0, blockIndex).reduce(function (a, b) { return a + b; }, 0) + i;
                    var span = document.createElement('span');
                    span.textContent = cabin.letters[letterIndex] || '';
                    block.appendChild(span);
                }
                header.appendChild(block);
            });
            header.appendChild(document.createElement('span'));
            deck.appendChild(header);

            cabin.rows.forEach(function (row) {
                if (row.type === 'exit') {
                    var marker = document.createElement('div');
                    marker.className = 'dh-exit-marker';
                    marker.innerHTML = '<span>ACİL ÇIKIŞ</span><span>ACİL ÇIKIŞ</span>';
                    deck.appendChild(marker);
                }

                var rowEl = document.createElement('div');
                rowEl.className = 'dh-seat-row';

                var numLeft = document.createElement('span');
                numLeft.className = 'dh-row-num';
                numLeft.textContent = row.number;
                rowEl.appendChild(numLeft);

                row.blocks.forEach(function (block) {
                    var blockEl = document.createElement('div');
                    blockEl.className = 'dh-seat-block';

                    block.forEach(function (seat) {
                        if (seat.seat === null) {
                            var gap = document.createElement('span');
                            gap.className = 'dh-seat-gap';
                            blockEl.appendChild(gap);
                            return;
                        }

                        var isTaken = occupied.indexOf(seat.seat) !== -1;
                        var fee = (payload.fees[name] || {})[seat.type] || 0;

                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'dh-seat dh-seat-' + (isTaken ? 'taken' : seat.type);
                        btn.dataset.seat = seat.seat;
                        btn.dataset.type = seat.type;
                        btn.dataset.cabin = name;
                        btn.dataset.fee = fee;
                        btn.dataset.label = isTaken ? '×' : seat.letter;
                        btn.textContent = btn.dataset.label;

                        if (isTaken) {
                            btn.disabled = true;
                            btn.title = seat.seat + ' — dolu';
                        } else {
                            btn.title = seat.seat + ' — ' + TYPE_LABELS[seat.type]
                                + (fee > 0 ? ' · ' + money(fee) : ' · ücretsiz');
                        }

                        blockEl.appendChild(btn);
                    });

                    rowEl.appendChild(blockEl);
                });

                var numRight = document.createElement('span');
                numRight.className = 'dh-row-num';
                numRight.textContent = row.number;
                rowEl.appendChild(numRight);

                deck.appendChild(rowEl);
            });

            body.appendChild(deck);
        });

        // Kuyruk servis alanı
        if (hasTail) {
            body.appendChild(serviceRow([
                ['ti-toilet-paper', 'Tuvalet'],
                ['ti-tools-kitchen-2', 'Mutfak'],
                ['ti-toilet-paper', 'Tuvalet']
            ]));
        }

        shell.appendChild(body);

        if (hasTail) {
            var tail = document.createElement('div');
            tail.className = 'dh-fuselage-tail';
            shell.appendChild(tail);
        } else {
            shell.appendChild(cutEdge('bottom'));
        }

        container.appendChild(shell);

        container.addEventListener('click', function (event) {
            var btn = event.target.closest('.dh-seat');
            if (btn && ! btn.disabled) {
                chooseSeat(direction, btn);
            }
        });
    }

    /** Kabin uçağın ucunda bitmiyorsa gövdenin devam ettiğini gösteren kesik. */
    function cutEdge(side) {
        var edge = document.createElement('div');
        edge.className = 'dh-fuselage-cut dh-fuselage-cut-' + side;
        return edge;
    }

    // ---- Seçim -------------------------------------------------------------

    function chooseSeat(direction, btn) {
        var seat = btn.dataset.seat;
        var paxIndex = activePax[direction];
        var passenger = passengerByIndex(paxIndex);

        if (! passenger) {
            return;
        }

        // Aynı koltuğa ikinci kez tıklamak seçimi kaldırır
        if (selection[direction][paxIndex] === seat) {
            delete selection[direction][paxIndex];
            render(direction);
            return;
        }

        if (! canOccupy(btn.dataset.type, passenger.type)) {
            showError(direction, occupancyError(seat, btn.dataset.type));
            return;
        }

        // Koltuk başka bir yolcuya atanmışsa ondan alınır
        Object.keys(selection[direction]).forEach(function (key) {
            if (selection[direction][key] === seat) {
                delete selection[direction][key];
            }
        });

        selection[direction][paxIndex] = seat;
        clearError(direction);

        // Koltuğu olmayan bir sonraki yolcuya otomatik geç
        var next = seatPassengers().find(function (p) {
            return selection[direction][p.index] === undefined;
        });
        if (next) {
            activePax[direction] = next.index;
        }

        render(direction);
    }

    function showError(direction, message) {
        var section = document.querySelector('.dh-seat-section[data-direction="' + direction + '"]');
        var box = section.querySelector('.dh-seat-error');

        if (! box) {
            box = document.createElement('div');
            box.className = 'dh-form-error dh-seat-error';
            box.innerHTML = '<i class="ti ti-alert-circle" aria-hidden="true"></i><span></span>';
            section.querySelector('.dh-seat-legend').before(box);
        }

        box.querySelector('span').textContent = message;
        box.hidden = false;
    }

    function clearError(direction) {
        var box = document.querySelector('.dh-seat-section[data-direction="' + direction + '"] .dh-seat-error');
        if (box) {
            box.hidden = true;
        }
    }

    // ---- Görsel güncelleme --------------------------------------------------

    function render(direction) {
        var section = document.querySelector('.dh-seat-section[data-direction="' + direction + '"]');
        var chosen = selection[direction];
        var legFee = 0;

        section.querySelectorAll('.dh-seat').forEach(function (btn) {
            var seat = btn.dataset.seat;
            var owner = null;

            Object.keys(chosen).forEach(function (key) {
                if (chosen[key] === seat) {
                    owner = parseInt(key, 10);
                }
            });

            btn.classList.toggle('dh-seat-selected', owner !== null);
            btn.textContent = owner !== null ? (owner + 1) : btn.dataset.label;
        });

        seatPassengers().forEach(function (p) {
            var label = section.querySelector('[data-seat-for="' + direction + '-' + p.index + '"]');
            var seat = chosen[p.index];

            if (! label) {
                return;
            }

            if (seat) {
                var btn = section.querySelector('.dh-seat[data-seat="' + seat + '"]');
                var fee = btn ? parseFloat(btn.dataset.fee) : 0;
                legFee += fee;
                label.textContent = seat + (fee > 0 ? ' · ' + money(fee) : ' · ücretsiz');
            } else {
                label.textContent = 'Koltuk seçilmedi';
            }
        });

        section.querySelectorAll('.dh-seat-pax-chip').forEach(function (chip) {
            var isActive = parseInt(chip.dataset.pax, 10) === activePax[direction];
            chip.classList.toggle('dh-seat-pax-chip-active', isActive);
            chip.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        var feeEl = section.querySelector('[data-leg-fee="' + direction + '"]');
        if (feeEl) {
            feeEl.textContent = money(legFee);
        }

        syncTotals();
        syncInputs();
    }

    function syncTotals() {
        var total = 0;

        Object.keys(selection).forEach(function (direction) {
            var section = document.querySelector('.dh-seat-section[data-direction="' + direction + '"]');
            if (! section) {
                return;
            }
            Object.keys(selection[direction]).forEach(function (paxIndex) {
                var btn = section.querySelector('.dh-seat[data-seat="' + selection[direction][paxIndex] + '"]');
                if (btn) {
                    total += parseFloat(btn.dataset.fee) || 0;
                }
            });
        });

        document.getElementById('seat-fee-total').textContent = money(total);
        document.getElementById('grand-total').textContent = money(ctx.fareTotal + total);
    }

    /** Seçimi forma gizli alan olarak yazar: seats[outbound][0] = "12A" */
    function syncInputs() {
        var box = document.getElementById('seat-inputs');
        box.innerHTML = '';

        Object.keys(selection).forEach(function (direction) {
            Object.keys(selection[direction]).forEach(function (paxIndex) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'seats[' + direction + '][' + paxIndex + ']';
                input.value = selection[direction][paxIndex];
                box.appendChild(input);
            });
        });
    }

    // ---- Başlangıç ---------------------------------------------------------

    function loadLeg(container) {
        var direction = container.dataset.direction;
        var url = '/api/flights/' + container.dataset.flightId
            + '/seat-map?cabin_class=' + encodeURIComponent(container.dataset.cabin);

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                if (! response.ok) {
                    throw new Error('Sunucu ' + response.status + ' döndü.');
                }
                return response.json();
            })
            .then(function (payload) {
                legData[direction] = payload;
                buildMap(container, payload);

                // Session'dan gelen önceki seçim geri yükleniyor
                var restored = (ctx.selected || {})[direction] || {};
                Object.keys(restored).forEach(function (paxIndex) {
                    selection[direction][parseInt(paxIndex, 10)] = restored[paxIndex];
                });

                render(direction);
            })
            .catch(function (error) {
                // Bu dal hem ağ/sunucu hatasını hem de buildMap() içindeki
                // çizim hatasını yakalıyor; ayırt edebilmek için konsola yazılıyor.
                console.error('[seat-map] ' + direction + ' bacağı yüklenemedi:', error);

                container.innerHTML = '<p class="dh-seat-loading">'
                    + 'Kabin planı yüklenemedi. Koltuk seçmeden devam edebilirsiniz; '
                    + 'sistem sizin için uygun bir koltuk atayacaktır.</p>';
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var maps = document.querySelectorAll('.dh-seat-map');
        var first = seatPassengers()[0];

        maps.forEach(function (container) {
            var direction = container.dataset.direction;
            selection[direction] = {};
            activePax[direction] = first ? first.index : null;
            loadLeg(container);
        });

        document.querySelectorAll('.dh-seat-pax-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                var direction = chip.dataset.direction;
                activePax[direction] = parseInt(chip.dataset.pax, 10);
                clearError(direction);
                render(direction);
            });
        });

        // "Koltuk seçmeden devam et" — seçimi temizleyip gönderir,
        // sunucu tarafında otomatik atama devreye girer.
        var skip = document.getElementById('skip-seats');
        if (skip) {
            skip.addEventListener('click', function () {
                document.getElementById('seat-inputs').innerHTML = '';
            });
        }
    });
})();
