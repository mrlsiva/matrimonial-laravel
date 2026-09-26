<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * Issues and verifies one-time passwords over email or SMS.
 * An identifier containing "@" is treated as an email address, otherwise a mobile number.
 */
class OtpService
{
    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_VERIFY_EMAIL = 'verify_email';
    public const PURPOSE_VERIFY_MOBILE = 'verify_mobile';

    public function __construct(private SmsService $sms) {}

    public function send(string $identifier, string $purpose): void
    {
        $code = (string) random_int(100000, 999999);
        $minutes = config('matrimony.otp_expiry_minutes');

        OtpCode::where('identifier', $identifier)->where('purpose', $purpose)->whereNull('used_at')->delete();

        OtpCode::create([
            'identifier' => $identifier,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($minutes),
        ]);

        // Local development only: emails/SMS go to the log, so surface the code on screen.
        if (app()->isLocal() && config('mail.default') === 'log' && app()->bound('session')) {
            session()->flash('dev_otp', $code);
        }

        if (self::isEmail($identifier)) {
            Mail::to($identifier)->send(new OtpMail($code, $purpose, $minutes));
        } else {
            $this->sms->send($identifier, "Your ".config('app.name')." OTP is {$code}. Valid for {$minutes} minutes. Do not share it with anyone.");
        }
    }

    public function verify(string $identifier, string $purpose, string $code): bool
    {
        $otp = OtpCode::where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= config('matrimony.otp_max_attempts')) {
            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['used_at' => now()]);

        return true;
    }

    public static function isEmail(string $identifier): bool
    {
        return str_contains($identifier, '@');
    }

    /** Normalise Indian mobile numbers to their last 10 digits. */
    public static function normaliseMobile(string $mobile): string
    {
        return substr(preg_replace('/\D/', '', $mobile), -10);
    }
}
