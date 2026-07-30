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

    $first  = $tickets->first();
    $flight = $first->flight;
    $o      = $flight->route->originAirport;
    $d      = $flight->route->destinationAirport;
@endphp
    <!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biniş Kartı</title>
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

                {{-- Giriş --}}
                <tr>
                    <td style="padding:30px 28px 20px; text-align:center;">
                        <p style="margin:0 0 6px; font-size:21px; font-weight:bold; color:{{ $ink }};">
                            Check-in tamamlandı
                        </p>
                        <p style="margin:0; font-size:14px; color:{{ $muted }}; line-height:1.5;">
                            {{ $flight->flight_number }} uçuşu için biniş kartlarınız aşağıdadır.<br>
                            Havalimanına gelirken yanınızda bulundurun.
                        </p>
                    </td>
                </tr>

                {{-- Her yolcu için biniş kartı --}}
                @foreach($tickets as $ticket)
                    <tr>
                        <td style="padding:0 28px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }}; border-radius:10px;">

                                {{-- Rota --}}
                                <tr>
                                    <td style="padding:20px 22px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size:11px; font-weight:bold; color:{{ $muted }}; letter-spacing:0.08em; text-transform:uppercase; padding-bottom:12px;">
                                                    Biniş Kartı
                                                </td>
                                                <td align="right" style="font-size:12px; font-weight:bold; color:{{ $redDark }}; background-color:{{ $redTint }}; padding:4px 10px; border-radius:4px;" width="1">
                                                    {{ $flight->flight_number }}
                                                </td>
                                            </tr>
                                        </table>

                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size:30px; font-weight:bold; color:{{ $ink }}; line-height:1;">
                                                    {{ $o->iata_code }}
                                                    <div style="font-size:12px; font-weight:normal; color:{{ $muted }}; padding-top:4px;">{{ $o->city }}</div>
                                                </td>
                                                <td align="center" style="font-size:18px; color:{{ $red }};">&#9992;</td>
                                                <td align="right" style="font-size:30px; font-weight:bold; color:{{ $ink }}; line-height:1;">
                                                    {{ $d->iata_code }}
                                                    <div style="font-size:12px; font-weight:normal; color:{{ $muted }}; padding-top:4px;">{{ $d->city }}</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Yolcu + koltuk --}}
                                <tr>
                                    <td style="padding:0 22px 16px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $cloud }}; border-radius:8px;">
                                            <tr>
                                                <td style="padding:14px 18px;">
                                                    <div style="font-size:10px; color:{{ $muted }}; letter-spacing:0.06em; text-transform:uppercase;">Yolcu</div>
                                                    <div style="font-size:16px; font-weight:bold; color:{{ $ink }}; padding-top:2px;">
                                                        {{ $ticket->passenger->first_name }} {{ $ticket->passenger->last_name }}
                                                    </div>
                                                    <div style="font-size:12px; color:{{ $muted }}; padding-top:2px;">
                                                        {{ $paxLabels[$ticket->passenger_type] }}
                                                    </div>
                                                </td>
                                                <td align="center" width="110" style="padding:14px 18px; border-left:1px dashed {{ $border }};">
                                                    <div style="font-size:10px; color:{{ $muted }}; letter-spacing:0.06em; text-transform:uppercase;">Koltuk</div>
                                                    <div style="font-size:28px; font-weight:bold; color:{{ $red }}; line-height:1.2;">
                                                        {{ $ticket->seat_number ?? '—' }}
                                                    </div>
                                                    @unless($ticket->seat_number)
                                                        <div style="font-size:11px; color:{{ $muted }};">Kucakta</div>
                                                    @endunless
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Uçuş bilgileri --}}
                                <tr>
                                    <td style="padding:0 22px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; border-top:1px solid {{ $cloud }};">
                                            <tr>
                                                <td style="color:{{ $muted }}; padding:10px 0 3px;">Tarih</td>
                                                <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:10px 0 3px;">
                                                    {{ $flight->departure_time->locale('tr')->isoFormat('D MMMM YYYY, dddd') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color:{{ $muted }}; padding:3px 0;">Kalkış</td>
                                                <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:3px 0;">
                                                    {{ $flight->departure_time->format('H:i') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color:{{ $muted }}; padding:3px 0;">Kabin</td>
                                                <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:3px 0;">
                                                    {{ $cabins[$ticket->cabin_class] }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="color:{{ $muted }}; padding:3px 0;">PNR</td>
                                                <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:3px 0; font-family:'Courier New', Courier, monospace; letter-spacing:0.05em;">
                                                    {{ $pnr }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                @endforeach

                {{-- Uyarı --}}
                <tr>
                    <td style="padding:4px 28px 28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $redTint }}; border-radius:8px;">
                            <tr>
                                <td style="padding:14px 18px; font-size:13px; color:{{ $redDark }}; line-height:1.6;">
                                    Uluslararası uçuşlarda kalkıştan <strong>3 saat</strong>, iç hatlarda <strong>2 saat</strong> önce
                                    havalimanında olmanızı öneririz. Biniş kapısı kalkıştan 20 dakika önce kapanır.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                {{-- Alt bilgi --}}
                <tr>
                    <td style="background-color:{{ $ink }}; padding:18px 28px; text-align:center;">
                        <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.6); line-height:1.6;">
                            Bu e-posta check-in işleminiz nedeniyle gönderilmiştir.<br>
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
