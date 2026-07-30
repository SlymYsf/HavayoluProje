<?php

namespace App\Services\Sms;

interface SmsGatewayInterface
{
    /**
     * @param string $phone E.164 formatında numara (+905321234567)
     */
    public function send(string $phone, string $message): SmsResult;
}
