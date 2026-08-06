{{-- Gecikme bildirim e-postası.
     Yolcuya bilgi netliği için tek bir ekran: özet kutuda güncel saat,
     hemen ardından planlanan saat ve gecikme süresi. Sebep ayrı bir kutuda
     gösteriliyor. --}}
@php
    $flight = $flight;
    $ticket = $tickets->first();
    $route = $flight->route;
    $passenger = $ticket->passenger;
    $estimated = $flight->estimatedDepartureTime();
    $estimatedArrival = $flight->estimatedArrivalTime();
@endphp

    <!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Uçuşunuzda gecikme</title>
</head>
<body style="font-family: Arial, sans-serif; background: #F4F4F6; margin: 0; padding: 40px 20px;">

<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; margin: 0 auto; background: #FFFFFF; border-radius: 8px; overflow: hidden;">

    {{-- Üst şerit --}}
    <tr>
        <td style="background: #8C0A20; padding: 24px 32px;">
            <h1 style="margin: 0; color: #FFFFFF; font-size: 20px;">Devlet Havayolları</h1>
        </td>
    </tr>

    {{-- Başlık --}}
    <tr>
        <td style="padding: 32px 32px 8px;">
            <p style="margin: 0 0 4px; color: #C8102E; font-size: 14px; font-weight: bold;">UÇUŞUNUZDA GECİKME</p>
            <h2 style="margin: 0; color: #1C1A1B; font-size: 24px;">
                Sayın {{ $passenger->first_name }} {{ $passenger->last_name }},
            </h2>
            <p style="margin: 12px 0 0; color: #5F5E5A; font-size: 15px; line-height: 1.6;">
                <strong>{{ $flight->departure_time->format('d.m.Y') }}</strong> tarihli
                <strong>{{ $flight->flight_number }}</strong> sefer sayılı uçuşunuzda
                <strong>{{ $flight->delay_minutes }} dakika</strong> gecikme oldu.
                Bu bildirimde güncel kalkış ve varış saatlerini bulabilirsiniz.
            </p>
        </td>
    </tr>

    {{-- Güncel kalkış özeti --}}
    <tr>
        <td style="padding: 24px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background: #F09595; border-radius: 6px;">
                <tr>
                    <td style="padding: 20px 24px;">
                        <p style="margin: 0 0 6px; color: #8C0A20; font-size: 12px; font-weight: bold;">
                            GÜNCEL KALKIŞ SAATİ
                        </p>
                        <p style="margin: 0; color: #1C1A1B; font-size: 28px; font-weight: bold;">
                            {{ $estimated->format('H:i') }}
                        </p>
                        <p style="margin: 6px 0 0; color: #5F5E5A; font-size: 13px;">
                            Planlanan saat: {{ $flight->departure_time->format('H:i') }} —
                            {{ $flight->delay_minutes }} dakika gecikme
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Rota --}}
    <tr>
        <td style="padding: 20px 32px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 40%; vertical-align: top;">
                        <p style="margin: 0; color: #5F5E5A; font-size: 12px;">Kalkış</p>
                        <p style="margin: 4px 0 0; color: #1C1A1B; font-size: 16px; font-weight: bold;">
                            {{ $route->originAirport->city }} ({{ $route->originAirport->iata_code }})
                        </p>
                        <p style="margin: 2px 0 0; color: #5F5E5A; font-size: 13px;">
                            {{ $route->originAirport->name }}
                        </p>
                    </td>
                    <td style="width: 20%; text-align: center; color: #C8102E; font-size: 20px;">→</td>
                    <td style="width: 40%; vertical-align: top;">
                        <p style="margin: 0; color: #5F5E5A; font-size: 12px;">Varış</p>
                        <p style="margin: 4px 0 0; color: #1C1A1B; font-size: 16px; font-weight: bold;">
                            {{ $route->destinationAirport->city }} ({{ $route->destinationAirport->iata_code }})
                        </p>
                        <p style="margin: 2px 0 0; color: #5F5E5A; font-size: 13px;">
                            {{ $route->destinationAirport->name }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Detaylar --}}
    <tr>
        <td style="padding: 20px 32px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="border-top: 1px solid #E2E1DC;">

                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid #E2E1DC;">
                        <span style="color: #5F5E5A; font-size: 13px;">Rezervasyon Kodu (PNR)</span>
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid #E2E1DC; text-align: right;">
                        <span style="color: #1C1A1B; font-size: 15px; font-weight: bold;">{{ $ticket->pnr }}</span>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 12px 0; border-bottom: 1px solid #E2E1DC;">
                        <span style="color: #5F5E5A; font-size: 13px;">Planlanan Varış</span>
                    </td>
                    <td style="padding: 12px 0; border-bottom: 1px solid #E2E1DC; text-align: right;">
                        <span style="color: #1C1A1B; font-size: 14px;">
                            {{ $flight->arrival_time->format('d.m.Y H:i') }}
                        </span>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 12px 0;">
                        <span style="color: #5F5E5A; font-size: 13px;">Tahmini Varış</span>
                    </td>
                    <td style="padding: 12px 0; text-align: right;">
                        <span style="color: #1C1A1B; font-size: 14px; font-weight: bold;">
                            {{ $estimatedArrival->format('d.m.Y H:i') }}
                        </span>
                    </td>
                </tr>

            </table>
        </td>
    </tr>

    {{-- Gecikme sebebi --}}
    <tr>
        <td style="padding: 0 32px 24px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="background: #FCE9EC; border-left: 3px solid #C8102E; border-radius: 4px;">
                <tr>
                    <td style="padding: 16px 20px;">
                        <p style="margin: 0 0 4px; color: #8C0A20; font-size: 12px; font-weight: bold;">
                            GECİKME SEBEBİ
                        </p>
                        <p style="margin: 0; color: #1C1A1B; font-size: 14px; line-height: 1.6;">
                            {{ $flight->delay_reason }}
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Bilgilendirme --}}
    <tr>
        <td style="padding: 0 32px 24px;">
            <p style="margin: 0 0 12px; color: #1C1A1B; font-size: 14px; font-weight: bold;">
                Önemli hatırlatmalar
            </p>
            <ul style="margin: 0; padding-left: 20px; color: #5F5E5A; font-size: 13px; line-height: 1.8;">
                <li>Check-in penceresi güncel kalkış saatinize göre yeniden hesaplanacaktır.</li>
                <li>Havalimanına planlanan kalkış saatinden en az iki saat önce gelmenizi rica ederiz.</li>
                <li>Gecikme süresi güncellenirse tarafınıza yeniden bilgilendirme yapılacaktır.</li>
            </ul>
        </td>
    </tr>

    {{-- Kapanış --}}
    <tr>
        <td style="padding: 20px 32px 32px; border-top: 1px solid #E2E1DC;">
            <p style="margin: 0 0 8px; color: #5F5E5A; font-size: 13px; line-height: 1.6;">
                Yaşanan aksaklıktan dolayı özür diler, anlayışınız için teşekkür ederiz.
            </p>
            <p style="margin: 0; color: #1C1A1B; font-size: 13px; font-weight: bold;">
                Devlet Havayolları
            </p>
        </td>
    </tr>

</table>

<p style="max-width: 600px; margin: 24px auto 0; color: #B4B2A9; font-size: 11px; text-align: center; line-height: 1.6;">
    Bu e-posta, {{ $ticket->pnr }} numaralı rezervasyonunuzdaki uçuşun durumundaki değişiklik nedeniyle
    otomatik olarak gönderilmiştir.
</p>

</body>
</html>
