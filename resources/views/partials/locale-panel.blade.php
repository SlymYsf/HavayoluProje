{{-- Dil ve bölge seçimi.
     Ülke listesi countries tablosundan gelir (bkz. Bölüm 4.13); bayrak
     görseli ISO kodundan türetilir, veritabanında saklanmaz.

     Native <select> yerine özel seçici kullanılıyor: bayrak gösterimi ve
     liste yüksekliğinin sınırlanması native öğeyle mümkün değil.

     ⚠️ Ülke adları veritabanında yalnızca Türkçe tutuluyor; İngilizce
     karşılık ISO kodundan intl eklentisiyle türetiliyor (Country modeli,
     display_name accessor'ı). --}}
@php
    $currentLocale = app()->getLocale();
    $currentRegion = session('region', request()->cookie('region', 'TR'));

    // Sıralama görüntülenen ada göre yapılıyor; veritabanı Türkçe adlara
    // göre sıralar, oysa kullanıcının gördüğü çevrilmiş addır.
    $countries = \App\Models\Country::orderBy('sort_order')->get()
        ->sortBy(fn ($c) => $c->display_name, SORT_LOCALE_STRING)
        ->values();

    $selectedCountry = $countries->firstWhere('iso_code', $currentRegion) ?? $countries->first();
    $locales = config('site.locales');
@endphp

<div class="dh-locale">
    <button type="button" class="dh-locale-trigger" id="locale-trigger"
            aria-haspopup="dialog" aria-expanded="false"
            aria-label="{{ __('Dil ve bölge seçimi') }}">
        <i class="ti ti-world" aria-hidden="true"></i>
        <span>{{ strtoupper($currentLocale) }}</span>
        <i class="ti ti-chevron-down" aria-hidden="true"></i>
    </button>

    <div class="dh-locale-panel" id="locale-panel" hidden>
        <form method="POST" action="{{ route('locale.update') }}">
            @csrf

            {{-- Ülke/bölge --}}
            <span class="dh-locale-label" id="region-label">{{ __('Ülke/bölge seçin') }}</span>

            <div class="dh-picker" data-picker="region">
                <button type="button" class="dh-picker-trigger"
                        aria-haspopup="listbox" aria-expanded="false" aria-labelledby="region-label">
                    <img class="dh-picker-flag" src="{{ $selectedCountry->flag_url }}" alt="">
                    <span class="dh-picker-text">{{ $selectedCountry->display_name }}</span>
                    <i class="ti ti-chevron-down" aria-hidden="true"></i>
                </button>

                <input type="hidden" name="region" value="{{ $selectedCountry->iso_code }}">

                <div class="dh-picker-panel" hidden>
                    <div class="dh-picker-search">
                        <input type="text" placeholder="{{ __('Ülke arayın') }}" autocomplete="off">
                    </div>

                    <div class="dh-picker-list" role="listbox">
                        @foreach ($countries as $country)
                            <button type="button" class="dh-picker-item" role="option"
                                    data-value="{{ $country->iso_code }}"
                                    data-label="{{ $country->display_name }}"
                                    data-flag="{{ $country->flag_url }}"
                                    aria-selected="{{ $country->iso_code === $currentRegion ? 'true' : 'false' }}">
                                <img src="{{ $country->flag_url }}" alt="" loading="lazy">
                                <span>{{ $country->display_name }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Dil --}}
            <span class="dh-locale-label" id="language-label">{{ __('Dil seçin') }}</span>

            <div class="dh-picker" data-picker="locale">
                <button type="button" class="dh-picker-trigger"
                        aria-haspopup="listbox" aria-expanded="false" aria-labelledby="language-label">
                    <i class="ti ti-language dh-picker-icon" aria-hidden="true"></i>
                    <span class="dh-picker-text">{{ $locales[$currentLocale]['label'] }}</span>
                    <i class="ti ti-chevron-down" aria-hidden="true"></i>
                </button>

                <input type="hidden" name="locale" value="{{ $currentLocale }}">

                <div class="dh-picker-panel" hidden>
                    <div class="dh-picker-list" role="listbox">
                        @foreach ($locales as $code => $meta)
                            <button type="button" class="dh-picker-item" role="option"
                                    data-value="{{ $code }}"
                                    data-label="{{ $meta['label'] }}"
                                    aria-selected="{{ $code === $currentLocale ? 'true' : 'false' }}">
                                <i class="ti ti-language" aria-hidden="true"></i>
                                <span>{{ $meta['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <label class="dh-locale-remember">
                <input type="checkbox" name="remember" value="1"
                    @checked(request()->cookie('locale'))>
                <span>{{ __('Seçimlerimi hatırla') }}</span>
            </label>

            <button type="submit" class="dh-btn-primary dh-locale-apply">{{ __('Değiştir') }}</button>
        </form>
    </div>
</div>
