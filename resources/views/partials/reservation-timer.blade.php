@if(!empty($expiresAt))
    <div class="dh-timer-bar" id="reservation-timer" data-expires="{{ $expiresAt }}">
        <div class="dh-timer-inner">
            <i class="ti ti-clock-hour-3" aria-hidden="true"></i>
            <span class="dh-timer-text">Rezervasyonunuzu tamamlamak için kalan süre</span>
            <span class="dh-timer-value" id="timer-value">--:--</span>
        </div>
    </div>
@endif
