@php
    $prefill = $prefill ?? [];
    $tripType = $prefill['trip_type'] ?? 'round_trip';
    $originLabel = $prefill['origin_label'] ?? '';
    $originId = $prefill['origin'] ?? '';
    $destinationLabel = $prefill['destination_label'] ?? '';
    $destinationId = $prefill['destination'] ?? '';
    $date = $prefill['date'] ?? '';
    $returnDate = $prefill['return_date'] ?? '';
    $cabinClass = $prefill['cabin_class'] ?? 'economy';
    $adult = $prefill['adult'] ?? 1;
    $child = $prefill['child'] ?? 0;
    $infant = $prefill['infant'] ?? 0;
    $student = $prefill['student'] ?? 0;
    $totalPax = (int)$adult + (int)$child + (int)$infant + (int)$student;
    $cabinShort = $cabinClass === 'economy' ? 'ECO' : 'BUS';
@endphp

<div class="dh-search-card">
    <div class="dh-trip-type">
        <label class="dh-radio">
            <input type="radio" name="trip_type" value="round_trip" {{ $tripType === 'round_trip' ? 'checked' : '' }}>
            <span>Gidiş - Dönüş</span>
        </label>
        <label class="dh-radio">
            <input type="radio" name="trip_type" value="one_way" {{ $tripType === 'one_way' ? 'checked' : '' }}>
            <span>Tek yön</span>
        </label>
        <label class="dh-radio dh-radio-disabled">
            <input type="radio" name="trip_type" value="stopover" disabled>
            <span>İstanbul'da Stopover <em>(yakında)</em></span>
        </label>
        <label class="dh-radio dh-radio-disabled">
            <input type="radio" name="trip_type" value="multi_city" disabled>
            <span>Çoklu uçuş <em>(yakında)</em></span>
        </label>
    </div>

    <form id="search-form" class="dh-search-form-v2">
        <div class="dh-route-field">
            <div class="dh-route-half">
                <label for="origin-search">Nereden</label>
                <input type="text" id="origin-search" class="dh-route-input" placeholder="Şehir ya da havalimanı" autocomplete="off" value="{{ $originLabel }}">
                <input type="hidden" id="origin" name="origin_airport_id" value="{{ $originId }}">
                <div id="origin-dropdown" class="dh-autocomplete" hidden></div>
            </div>
            <button type="button" id="swap-airports" class="dh-swap-btn" aria-label="Kalkış ve varış noktasını değiştir">
                <i class="ti ti-arrows-exchange" aria-hidden="true"></i>
            </button>
            <div class="dh-route-half">
                <label for="destination-search">Nereye</label>
                <input type="text" id="destination-search" class="dh-route-input" placeholder="Şehir ya da havalimanı" autocomplete="off" value="{{ $destinationLabel }}">
                <input type="hidden" id="destination" name="destination_airport_id" value="{{ $destinationId }}">
                <div id="destination-dropdown" class="dh-autocomplete" hidden></div>
            </div>
        </div>

        <div class="dh-date-field">
            <label for="departure-date">Gidiş</label>
            <input type="text" id="departure-date" name="date" placeholder="Tarih seçin" value="{{ $date }}">
        </div>

        <div class="dh-date-field" id="return-date-field" {{ $tripType === 'one_way' ? 'hidden' : '' }}>
            <label for="return-date">Dönüş</label>
            <input type="text" id="return-date" name="return_date" placeholder="Tarih seçin" value="{{ $returnDate }}">
        </div>

        <div class="dh-passenger-wrapper" id="passenger-wrapper">
            <div class="dh-passenger-field" id="passenger-toggle">
                <label>Yolcular</label>
                <div class="dh-passenger-value" id="passenger-summary">
                    <span class="dh-pax-main-text">{{ $totalPax }} Yolcu</span>
                    <span class="dh-pax-sub-text">{{ $cabinShort }}</span>
                </div>
            </div>

            <input type="hidden" name="cabin_class" id="cabin_class" value="{{ $cabinClass }}">
            <input type="hidden" name="pax_adult" id="pax_adult" value="{{ $adult }}">
            <input type="hidden" name="pax_child" id="pax_child" value="{{ $child }}">
            <input type="hidden" name="pax_infant" id="pax_infant" value="{{ $infant }}">
            <input type="hidden" name="pax_student" id="pax_student" value="{{ $student }}">

            <div class="dh-passenger-dropdown" id="passenger-dropdown" hidden>
                <div class="dh-dropdown-header">Kabin ve yolcu seçimi</div>

                <div class="dh-cabin-tabs">
                    <label class="dh-cabin-tab {{ $cabinClass === 'economy' ? 'active' : '' }}" id="tab-eco">
                        <input type="radio" name="cabin_selection" value="economy" {{ $cabinClass === 'economy' ? 'checked' : '' }}>
                        <span class="dh-radio-circle"></span> Economy Class
                    </label>
                    <label class="dh-cabin-tab {{ $cabinClass === 'business' ? 'active' : '' }}" id="tab-bus">
                        <input type="radio" name="cabin_selection" value="business" {{ $cabinClass === 'business' ? 'checked' : '' }}>
                        <span class="dh-radio-circle"></span> Business Class
                    </label>
                </div>

                <div class="dh-pax-list">
                    <div class="dh-pax-row">
                        <div class="dh-pax-info">
                            <strong>Yetişkin <i class="ti ti-info-circle" aria-hidden="true"></i></strong>
                            <span>12 +</span>
                        </div>
                        <div class="dh-pax-controls">
                            <button type="button" class="dh-pax-btn" data-type="adult" data-action="minus">-</button>
                            <span class="dh-pax-count" id="display_adult">{{ $adult }}</span>
                            <button type="button" class="dh-pax-btn" data-type="adult" data-action="plus">+</button>
                        </div>
                    </div>
                    <div class="dh-pax-row">
                        <div class="dh-pax-info">
                            <strong>Çocuk <i class="ti ti-info-circle" aria-hidden="true"></i></strong>
                            <span>2 - 12</span>
                        </div>
                        <div class="dh-pax-controls">
                            <button type="button" class="dh-pax-btn" data-type="child" data-action="minus">-</button>
                            <span class="dh-pax-count" id="display_child">{{ $child }}</span>
                            <button type="button" class="dh-pax-btn" data-type="child" data-action="plus">+</button>
                        </div>
                    </div>
                    <div class="dh-pax-row">
                        <div class="dh-pax-info">
                            <strong>Bebek <i class="ti ti-info-circle" aria-hidden="true"></i></strong>
                            <span>2 yaş altı</span>
                        </div>
                        <div class="dh-pax-controls">
                            <button type="button" class="dh-pax-btn" data-type="infant" data-action="minus">-</button>
                            <span class="dh-pax-count" id="display_infant">{{ $infant }}</span>
                            <button type="button" class="dh-pax-btn" data-type="infant" data-action="plus">+</button>
                        </div>
                    </div>
                    <div class="dh-pax-row">
                        <div class="dh-pax-info">
                            <strong>Öğrenci <i class="ti ti-info-circle" aria-hidden="true"></i></strong>
                            <span>12 - 35</span>
                        </div>
                        <div class="dh-pax-controls">
                            <button type="button" class="dh-pax-btn" data-type="student" data-action="minus">-</button>
                            <span class="dh-pax-count" id="display_student">{{ $student }}</span>
                            <button type="button" class="dh-pax-btn" data-type="student" data-action="plus">+</button>
                        </div>
                    </div>
                </div>

                <div class="dh-pax-footer">
                    <button type="button" class="dh-pax-cancel" id="pax-cancel">Vazgeç</button>
                    <button type="button" class="dh-btn-primary dh-pax-apply" id="pax-apply">Uygula</button>
                </div>

            </div>
        </div>

        <div class="dh-form-error" id="search-error" hidden>
            <i class="ti ti-alert-circle" aria-hidden="true"></i>
            <span id="search-error-text"></span>
        </div>
        
        <div class="dh-form-actions">
            <button type="submit" class="dh-btn-primary dh-search-submit">
                Uçuş ara <i class="ti ti-arrow-right" aria-hidden="true"></i>
            </button>
        </div>
    </form>
</div>

<div id="destinations-modal" class="dh-modal" hidden>
    <div class="dh-modal-backdrop" id="modal-backdrop"></div>
    <div class="dh-modal-box">
        <div class="dh-modal-header">
            <span>Aşağıdaki ülke ve şehirler arasından seçim yapabilirsiniz.</span>
            <button type="button" id="modal-close" class="dh-modal-close" aria-label="Kapat">
                <i class="ti ti-x" aria-hidden="true"></i>
            </button>
        </div>
        <div class="dh-modal-body">
            <div class="dh-modal-col">
                <div class="dh-modal-col-title">
                    <i class="ti ti-world" aria-hidden="true"></i>
                    Ülke / Bölge (<span id="country-count">0</span>)
                </div>
                <div id="country-list" class="dh-modal-list"></div>
            </div>
            <div class="dh-modal-col">
                <div class="dh-modal-col-title">
                    <i class="ti ti-plane" aria-hidden="true"></i>
                    Havalimanı (<span id="airport-count">0</span>)
                </div>
                <div id="airport-list" class="dh-modal-list"></div>
            </div>
        </div>
    </div>
</div>
