<?php

namespace App\Services;

use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    private const CODE_LIFETIME_MINUTES = 3;

    /**
     * Generate a fresh 6-digit code for the given email/purpose, invalidating
     * any previously issued (unconsumed) code for the same pair, then email it.
     */
    public function generateAndSend(string $email, string $purpose): void
    {
        OtpCode::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->delete();

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
}
