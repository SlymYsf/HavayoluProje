<?php

namespace App\Notifications;

/**
 * Rezervasyon bildirim türleri.
 *
 * Her tür bir mail sınıfı ve bir SMS metni üretir; iş sınıfı yalnızca
 * bu enum'a bakarak hangisini göndereceğini biliyor.
 */
enum NotificationType: string
{
    case ReservationConfirmed = 'reservation_confirmed';
    case BoardingPass         = 'boarding_pass';
    case CheckInReminder      = 'checkin_reminder';

    case ReservationCancelled = 'reservation_cancelled';
}
