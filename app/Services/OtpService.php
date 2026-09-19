<?php

namespace App\Services;

use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    private const CODE_LIFETIME_MINUTES = 3;

    private const RESEND_COOLDOWN_SECONDS = 45;

    private const RESEND_LIMIT_PER_HOUR = 3;

    /**
     * Generate a fresh 6-digit code for the given email/purpose, invalidating
     * any previously issued (unconsumed) code for the same pair, then email it.
     */
    public function generateAndSend(string $email, string $purpose): void
    {
        OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = (string) random_int(100000, 999999);

        OtpCode::create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => hash('sha256', $code),
            'expires_at' => now()->addMinutes(self::CODE_LIFETIME_MINUTES),
            'attempts' => 0,
            'created_at' => now(),
        ]);

        Mail::to($email)->send(new OtpCodeMail($code, $purpose));
    }

    /**
     * Check the §6.3 resend rules (45s cooldown, 3/hour), based on how many
     * codes have actually been sent for this email/purpose — wrong-code
     * verify attempts never count here, only sends do.
     *
     * @return true|array{reason: string, retry_after_seconds?: int}
     */
    public function canResend(string $email, string $purpose): true|array
    {
        $lastSentAt = OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->latest('created_at')
            ->value('created_at');

        if ($lastSentAt && $lastSentAt->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            return [
                'reason' => 'cooldown',
                'retry_after_seconds' => self::RESEND_COOLDOWN_SECONDS - now()->diffInSeconds($lastSentAt),
            ];
        }

        $sentLastHour = OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($sentLastHour >= self::RESEND_LIMIT_PER_HOUR) {
            return ['reason' => 'limit'];
        }

        return true;
    }

    /**
     * Verify a submitted code. On success, marks it consumed and returns the
     * OtpCode row. On failure (wrong/expired/none found), increments the
     * attempts counter (if a pending code exists) and returns null.
     */
    public function verify(string $email, string $code, string $purpose): ?OtpCode
    {
        $otp = OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        if (! $otp || ! hash_equals($otp->code_hash, hash('sha256', $code))) {
            $otp?->increment('attempts');

            return null;
        }

        $otp->update(['consumed_at' => now()]);

        return $otp;
    }
}
