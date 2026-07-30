<?php

namespace App\Notifications;

/**
 * Zamanlanmış hatırlatma türleri.
 *
 * Yeni bir hatırlatma eklemek için tek yapılacak: buraya bir case ve iki
 * match koluna satır eklemek. Veritabanı değişikliği gerekmiyor.
 */
enum ReminderType: string
{
    case CheckIn24h = 'checkin_24h';

    /** Kalkıştan kaç saat önce gönderilecek. */
    public function hoursBefore(): int
    {
        return match ($this) {
            self::CheckIn24h => 24,
        };
    }

    /** Hangi bildirim içeriğiyle eşleşiyor. */
    public function notification(): NotificationType
    {
        return match ($this) {
            self::CheckIn24h => NotificationType::CheckInReminder,
        };
    }
}
