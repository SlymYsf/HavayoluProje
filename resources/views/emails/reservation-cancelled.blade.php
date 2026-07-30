@php
    $red     = '#C8102E';
    $redDark = '#8C0A20';
    $redTint = '#FCE9EC';
    $ink     = '#1C1A1B';
    $muted   = '#5F5E5A';
    $border  = '#E2E1DC';
    $cloud   = '#F4F4F6';

    $paxLabels = ['adult'=>'Yetişkin','child'=>'Çocuk','infant'=>'Bebek','student'=>'Öğrenci'];
@endphp
    <!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervasyon İptali</title>
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
                            Rezervasyonunuz iptal edildi
                        </p>
                        <p style="margin:0 0 20px; font-size:14px; color:{{ $muted }}; line-height:1.5;">
                            Aşağıdaki rezervasyon iptal işlemi tamamlanmıştır.<br>
                            Biletleriniz artık geçerli değildir.
                        </p>

                        <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="background-color:{{ $cloud }}; border-radius:10px;">
                            <tr>
                                <td style="padding:14px 32px; text-align:center;">
                                    <div style="font-size:11px; font-weight:bold; color:{{ $muted }}; letter-spacing:0.08em; text-transform:uppercase;">
                                        İptal Edilen Rezervasyon
                                    </div>
                                    <div style="font-size:24px; font-weight:bold; color:{{ $muted }}; letter-spacing:0.06em; padding-top:4px; font-family:'Courier New', Courier, monospace; text-decoration:line-through;">
                                        {{ $pnr }}
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                @foreach($tickets->groupBy('flight_id') as $flightTickets)
                    @php
                        $f = $flightTickets->first()->flight;
                        $o = $f->route->originAirport;
                        $d = $f->route->destinationAirport;
                    @endphp
                    <tr>
                        <td style="padding:0 28px 16px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid {{ $border }}; border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 20px; border-bottom:1px solid {{ $cloud }};">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="font-size:12px; font-weight:bold; color:{{ $muted }}; background-color:{{ $cloud }}; padding:4px 10px; border-radius:4px;" width="1">
                                                    {{ $f->flight_number }}
                                                </td>
                                                <td align="right" style="font-size:18px; font-weight:bold; color:{{ $muted }};">
                                                    {{ $o->iata_code }} &rarr; {{ $d->iata_code }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:12px 20px; font-size:13px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="color:{{ $muted }}; padding:3px 0;">Kalkış</td>
                                                <td align="right" style="color:{{ $ink }}; padding:3px 0;">
                                                    {{ $f->departure_time->locale('tr')->isoFormat('D MMMM YYYY') }} · {{ $f->departure_time->format('H:i') }}
                                                </td>
                                            </tr>
                                            @foreach($flightTickets as $t)
                                                <tr>
                                                    <td style="color:{{ $muted }}; padding:3px 0;">
                                                        {{ $paxLabels[$t->passenger_type] }}
                                                    </td>
                                                    <td align="right" style="color:{{ $ink }}; padding:3px 0;">
                                                        {{ $t->passenger->first_name }} {{ $t->passenger->last_name }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <td style="padding:0 28px 24px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:{{ $redTint }}; border-radius:10px;">
                            <tr>
                                <td style="padding:16px 20px; font-size:14px; font-weight:bold; color:{{ $redDark }};">İade edilecek tutar</td>
                                <td align="right" style="padding:16px 20px; font-size:20px; font-weight:bold; color:{{ $red }}; font-family:'Courier New', Courier, monospace;">
                                    {{ number_format($refundTotal, 0, ',', '.') }}₺
                                </td>
                            </tr>
                        </table>
                        <p style="margin:12px 0 0; font-size:13px; color:{{ $muted }}; line-height:1.6; text-align:center;">
                            İade işlemi, ödemenin yapıldığı karta 3-5 iş günü içinde yansıtılır.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:0 28px 30px; text-align:center;">
                        <a href="{{ url('/') }}"
                           style="display:inline-block; background-color:{{ $red }}; color:#FFFFFF; font-size:15px; font-weight:bold; text-decoration:none; padding:14px 34px; border-radius:8px;">
                            Yeni uçuş ara
                        </a>
                    </td>
                </tr>

                <tr>
                    <td style="background-color:{{ $ink }}; padding:18px 28px; text-align:center;">
                        <p style="margin:0; font-size:11px; color:rgba(255,255,255,0.6); line-height:1.6;">
                            Bu e-posta rezervasyon iptaliniz nedeniyle gönderilmiştir.<br>
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
