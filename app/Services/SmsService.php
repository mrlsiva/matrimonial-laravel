<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin SMS gateway wrapper. The "log" driver writes messages to the application log,
 * which is useful for local development. Add a real provider (MSG91, Twilio, Fast2SMS ...)
 * as another match arm and set SMS_DRIVER accordingly.
 */
class SmsService
{
    public function send(string $mobile, string $message): void
    {
        match (config('matrimony.sms_driver')) {
            'log' => Log::info("[SMS to {$mobile}] {$message}"),
            default => throw new RuntimeException('SMS driver ['.config('matrimony.sms_driver').'] is not configured.'),
        };
    }
}
