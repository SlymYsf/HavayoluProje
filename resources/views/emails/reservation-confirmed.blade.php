@php
    $red     = '#C8102E';
    $redDark = '#8C0A20';
    $redTint = '#FCE9EC';
    $ink     = '#1C1A1B';
    $muted   = '#5F5E5A';
    $border  = '#E2E1DC';
    $cloud   = '#F4F4F6';

    $paxLabels = ['adult'=>'Yetişkin','child'=>'Çocuk','infant'=>'Bebek','student'=>'Öğrenci'];
    $cabins    = ['economy'=>'Economy','premium_economy'=>'Premium Economy','business'=>'Business'];
@endphp
    <!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyon Onayı</title>
</head>
<body style="margin:0; padding:0; background-color:{{ $cloud }}; font-family:Arial, Helvetica, sans-serif; color:{{ $ink }};">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $cloud }}; padding:24px 12px;">
    <tr>
        <td align="center">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#FFFFFF; border-radius:10px; overflow:hidden;">

                {{-- Başlık --}}
                <tr>
                    <td style="background-color:{{ $ink }}; padding:20px 28px;">
                        <span style="color:#FFFFFF; font-size:17px; font-weight:bold; letter-spacing:0.02em;">Devlet Havayolları</span>
                    </td>
                </tr>

                {{-- Onay + PNR --}}
                <tr>
                    <td style="padding:32px 28px 24px; text-align:center;">
                        <p style="margin:0 0 6px; font-size:21px; font-weight:bold; color:{{ $ink }};">
                            Rezervasyonunuz tamamlandı
                        </p>
                        <p style="margin:0 0 22px; font-size:14px; color:{{ $muted }}; line-height:1.5;">
                            Aşağıdaki rezervasyon kodunu saklayın. Check-in ve bilet yönetimi için gereklidir.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="background-color:{{ $redTint }}; border-radius:10px;">
                            <tr>
                                <td style="padding:16px 36px; text-align:center;">
                                    <div style="font-size:11px; font-weight:bold; color:{{ $redDark }}; letter-spacing:0.08em; text-transform:uppercase;">
                                        Rezervasyon Kodu
                                    </div>
                                    <div style="font-size:28px; font-weight:bold; color:{{ $redDark }}; letter-spacing:0.06em; padding-top:4px; font-family:'Courier New', Courier, monospace;">
                                        {{ $pnr }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Uçuşlar --}}
                @foreach($tickets->groupBy('flight_id') as $flightTickets)
                    @php
                        $f = $flightTickets->first()->flight;
                        $o = $f->route->originAirport;
                        $d = $f->route->destinationAirport;
                        $dayDiff = $f->departure_time->startOfDay()->diffInDays($f->arrival_time->startOfDay());
                    @endphp
                    <tr>
                        <td style="padding:0 28px 20px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }}; border-radius:10px;">

                                <tr>
                                    <td style="padding:16px 20px; border-bottom:1px solid {{ $cloud }};">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size:12px; font-weight:bold; color:{{ $redDark }}; background-color:{{ $redTint }}; padding:4px 10px; border-radius:4px;" width="1">
                                                    {{ $f->flight_number }}
                                                </td>
                                                <td align="right" style="font-size:18px; font-weight:bold; color:{{ $ink }};">
                                                    {{ $o->iata_code }} &rarr; {{ $d->iata_code }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td style="padding:14px 20px; border-bottom:1px solid {{ $cloud }};">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px;">
                                            <tr>
                                                <td style="color:{{ $muted }}; padding:3px 0;">Kalkış</td>
                                                <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:3px 0;">
                                                    {{ $f->departure_time->locale('tr')->isoFormat('D MMMM YYYY, dddd') }} · {{ $f->departure_time->format('H:i') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color:{{ $muted }}; padding:3px 0;">Varış</td>
                                                <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:3px 0;">
                                                    {{ $f->arrival_time->format('H:i') }}@if($dayDiff > 0) <span style="color:{{ $red }}; font-size:11px;">+{{ $dayDiff }} gün</span>@endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color:{{ $muted }}; padding:3px 0;">Uçak · Kabin</td>
                                                <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:3px 0;">
                                                    {{ $f->aircraft->model }} · {{ $cabins[$flightTickets->first()->cabin_class] }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                @foreach($flightTickets as $ticket)
                                    <tr>
                                        <td style="padding:12px 20px; {{ $loop->last ? '' : 'border-bottom:1px solid ' . $cloud . ';' }}">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td style="font-size:14px; color:{{ $ink }};">
                                                        <strong>{{ $ticket->passenger->first_name }} {{ $ticket->passenger->last_name }}</strong><br>
                                                        <span style="font-size:12px; color:{{ $muted }};">{{ $paxLabels[$ticket->passenger_type] }}</span>
                                                    </td>
                                                    <td align="center" width="80" style="font-size:12px; color:{{ $muted }};">
                                                        @if($ticket->seat_number)
                                                            Koltuk<br>
                                                            <span style="font-size:18px; font-weight:bold; color:{{ $red }};">{{ $ticket->seat_number }}</span>
                                                        @else
                                                            Kucakta
                                                        @endif
                                                    </td>
                                                    <td align="right" width="90" style="font-size:14px; font-weight:bold; color:{{ $ink }}; font-family:'Courier New', Courier, monospace;">
                                                        {{ number_format($ticket->final_price, 0, ',', '.') }}₺
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach

                            </table>
                        </td>
                    </tr>
                @endforeach

                {{-- Toplam --}}
                <tr>
                    <td style="padding:0 28px 24px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $cloud }}; border-radius:10px;">
                            <tr>
                                <td style="padding:16px 20px; font-size:14px; font-weight:bold; color:{{ $ink }};">Ödenen tutar</td>
                                <td align="right" style="padding:16px 20px; font-size:20px; font-weight:bold; color:{{ $red }}; font-family:'Courier New', Courier, monospace;">
                                    {{ number_format($total, 0, ',', '.') }}₺
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Check-in --}}
                <tr>
                    <td style="padding:0 28px 28px; text-align:center;">
                        <p style="margin:0 0 16px; font-size:13px; color:{{ $muted }}; line-height:1.6;">
                            Check-in, kalkıştan 24 saat önce açılır.<br>
                            Rezervasyon kodunuz ve soyadınızla giriş yapabilirsiniz.
                        </p>
                        <a href="{{ url('/check-in') }}?pnr={{ urlencode($pnr) }}&last_name={{ urlencode($tickets->first()->passenger->last_name) }}"
                           style="display:inline-block; background-color:{{ $red }}; color:#FFFFFF; font-size:14px; font-weight:bold; text-decoration:none; padding:13px 30px; border-radius:8px;">
                            Check-in sayfasına git
                        </a>
                    </td>
                </tr>

                {{-- Alt bilgi --}}
                <tr>
                    <td style="background-color:{{ $ink }}; padding:18px 28px; text-align:center;">
                        <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.6); line-height:1.6;">
                            Bu e-posta rezervasyonunuz nedeniyle gönderilmiştir.<br>
                            Devlet Havayolları A.O. &copy; {{ date('Y') }}
                        </p>
                    </td>
                </tr>

            </table>

        </td>
    </tr>
</table>

</body>
</html>
