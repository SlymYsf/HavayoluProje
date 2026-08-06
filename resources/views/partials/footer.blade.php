{{-- Ortak footer.
     Menü ve bağlantı yapısı üst mega menüyle aynı ruhta; buraya eklenen
     her yeni bağlantı tüm sayfalarda otomatik görünür. --}}
<footer class="dh-footer">
    <div class="dh-footer-columns">
        <div>
            <h3>{{ __('Bilet al ve yönet') }}</h3>
            <ul>
                <li><a href="/?sekme=ucak">{{ __('Uçak bileti') }}</a></li>
                <li><a href="/?sekme=checkin">{{ __('Check-in') }}</a></li>
                <li><a href="/?sekme=yonetim">{{ __('Bilet yönetimi') }}</a></li>
                <li><a href="/?sekme=durum">{{ __('Uçuş durumu') }}</a></li>
            </ul>
        </div>
        <div>
            <h3>{{ __('Deneyim') }}</h3>
            <ul>
                <li><a href="{{ route('static.cabin', ['class' => 'business']) }}">Business Class</a></li>
                <li><a href="{{ route('static.cabin', ['class' => 'economy']) }}">Economy Class</a></li>
                <li><a href="{{ route('static.fleet') }}">{{ __('Filo') }}</a></li>
                <li><a href="{{ route('static.yesilkoy') }}">{{ __('Yeşilköy Havalimanı') }}</a></li>
            </ul>
        </div>
        <div>
            <h3>{{ __('Yardım') }}</h3>
            <ul>
                <li><a href="#">{{ __('Rezervasyon ve biletleme') }}</a></li>
                <li><a href="#">{{ __('Ücret koşulları') }}</a></li>
                <li><a href="#">{{ __('Yardım merkezi') }}</a></li>
                <li><a href="#">{{ __('Bize ulaşın') }}</a></li>
            </ul>
        </div>
        <div>
            <h3>Devlet Havayolları</h3>
            <ul>
                <li><a href="#">{{ __('Hakkımızda') }}</a></li>
                <li><a href="{{ route('static.fleet') }}">{{ __('Filo') }}</a></li>
                <li><a href="#">{{ __('Basın odası') }}</a></li>
                <li><a href="#">{{ __('Yatırımcı ilişkileri') }}</a></li>
            </ul>
        </div>
    </div>

    <div class="dh-footer-bottom">
        <div class="dh-footer-brand">
            <img src="{{ asset('images/logo.png') }}" alt="{{ __('Devlet Havayolları logosu') }}">
            <span>Devlet Havayolları</span>
        </div>
        <div class="dh-footer-social">
            <a href="#" aria-label="X"><i class="ti ti-brand-x" aria-hidden="true"></i></a>
            <a href="#" aria-label="Facebook"><i class="ti ti-brand-facebook" aria-hidden="true"></i></a>
            <a href="#" aria-label="Instagram"><i class="ti ti-brand-instagram" aria-hidden="true"></i></a>
            <a href="#" aria-label="YouTube"><i class="ti ti-brand-youtube" aria-hidden="true"></i></a>
            <a href="#" aria-label="LinkedIn"><i class="ti ti-brand-linkedin" aria-hidden="true"></i></a>
        </div>
    </div>

    <div class="dh-footer-legal">
        <a href="#">{{ __('Gizlilik ve Çerez Politikası') }}</a>
        <a href="#">{{ __('Yasal Uyarı') }}</a>
        <a href="#">{{ __('Yolcu Hakları') }}</a>
    </div>

    <p class="dh-footer-copyright">{{ __('Devlet Havayolları A.O. Her hakkı saklıdır. © :year', ['year' => 2026]) }}</p>
</footer>
