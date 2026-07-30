@php
    $red     = '#C8102E';
    $redDark = '#8C0A20';
    $redTint = '#FCE9EC';
    $ink     = '#1C1A1B';
    $muted   = '#5F5E5A';
    $border  = '#E2E1DC';
    $cloud   = '#F4F4F6';

    $paxLabels = ['adult'=>'Yetişkin','child'=>'Çocuk','infant'=>'Bebek','student'=>'Öğrenci'];

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
    <title>Check-in Açıldı</title>
</head>
<body style="margin:0; padding:0; background-color:{{ $cloud }}; font-family:Arial, Helvetica, sans-serif; color:{{ $ink }};">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $cloud }}; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#FFFFFF; border-radius:10px; overflow:hidden;">

                <tr>
                    <td style="background-color:{{ $ink }}; padding:20px 28px;">
                        <span style="color:#FFFFFF; font-size:17px; font-weight:bold; letter-spacing:0.02em;">Devlet Havayolları</span>
                    </td>
                </tr>

                <tr>
                    <td style="padding:32px 28px 22px; text-align:center;">
                        <p style="margin:0 0 6px; font-size:21px; font-weight:bold; color:{{ $ink }};">
                            Check-in işlemleri açıldı
                        </p>
                        <p style="margin:0; font-size:14px; color:{{ $muted }}; line-height:1.5;">
                            {{ $flight->flight_number }} uçuşunuz için online check-in yapabilirsiniz.<br>
                            Kalkışa 24 saatten az bir süre kaldı.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 28px 20px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }}; border-radius:10px;">
                            <tr>
                                <td style="padding:18px 22px; border-bottom:1px solid {{ $cloud }};">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="font-size:12px; font-weight:bold; color:{{ $redDark }}; background-color:{{ $redTint }}; padding:4px 10px; border-radius:4px;" width="1">
                                                {{ $flight->flight_number }}
                                            </td>
                                            <td align="right" style="font-size:18px; font-weight:bold; color:{{ $ink }};">
                                                {{ $o->iata_code }} &rarr; {{ $d->iata_code }}
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:14px 22px; font-size:13px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                        <tr>
                                            <td style="color:{{ $muted }}; padding:3px 0;">Kalkış</td>
                                            <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:3px 0;">
                                                {{ $flight->departure_time->locale('tr')->isoFormat('D MMMM YYYY, dddd') }} · {{ $flight->departure_time->format('H:i') }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td style="color:{{ $muted }}; padding:3px 0;">Güzergah</td>
                                            <td align="right" style="color:{{ $ink }}; font-weight:bold; padding:3px 0;">
                                                {{ $o->city }} &rarr; {{ $d->city }}
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
                            <tr>
                                <td style="padding:0 22px 18px;">
                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $cloud }};">
                                        @foreach($tickets as $t)
                                            <tr>
                                                <td style="padding:10px 0 2px; font-size:14px; font-weight:bold; color:{{ $ink }};">
                                                    {{ $t->passenger->first_name }} {{ $t->passenger->last_name }}
                                                </td>
                                                <td align="right" style="padding:10px 0 2px; font-size:12px; color:{{ $muted }};">
                                                    {{ $paxLabels[$t->passenger_type] }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 28px 30px; text-align:center;">
                        <a href="{{ $checkInUrl }}"
                           style="display:inline-block; background-color:{{ $red }}; color:#FFFFFF; font-size:15px; font-weight:bold; text-decoration:none; padding:14px 34px; border-radius:8px;">
                            Check-in yap
                        </a>
                        <p style="margin:14px 0 0; font-size:12px; color:{{ $muted }};">
                            Check-in, kalkıştan 20 dakika öncesine kadar açıktır.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="background-color:{{ $ink }}; padding:18px 28px; text-align:center;">
                        <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.6); line-height:1.6;">
                            Bu e-posta yaklaşan uçuşunuz nedeniyle gönderilmiştir.<br>
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
