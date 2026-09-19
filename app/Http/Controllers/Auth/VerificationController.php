<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResendCodeRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VerificationController extends Controller
{
    use ApiResponse;

    private const RESET_TOKEN_TTL_MINUTES = 10;

    private const RESET_TOKEN_CACHE_PREFIX = 'password_reset_token:';

    public function __construct(private readonly OtpService $otpService) {}

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $otp = $this->otpService->verify($request->email, $request->code, $request->purpose);

        if (! $otp) {
            return $this->error('الكود غير صحيح أو منتهي الصلاحية.', [], 422);
        }

        if ($request->purpose === 'register') {
            $user = User::where('email', $request->email)->firstOrFail();
            $user->forceFill(['email_verified_at' => now()])->save();

            $token = $user->createToken('auth-token')->plainTextToken;

            return $this->success([
                'token' => $token,
                'user' => new UserResource($user),
            ], 'تم تأكيد البريد الإلكتروني بنجاح.');
        }

        $resetToken = Str::random(64);
        Cache::put(
            self::RESET_TOKEN_CACHE_PREFIX.hash('sha256', $resetToken),
            $request->email,
            now()->addMinutes(self::RESET_TOKEN_TTL_MINUTES)
        );

        return $this->success([
            'reset_token' => $resetToken,
        ], 'تم التحقق من الكود بنجاح.');
    }

    public function resendCode(ResendCodeRequest $request): JsonResponse
    {
        $check = $this->otpService->canResend($request->email, $request->purpose);

        if ($check !== true) {
            return $check['reason'] === 'cooldown'
                ? $this->error(
                    "الرجاء الانتظار {$check['retry_after_seconds']} ثانية قبل طلب كود جديد.",
                    [],
                    429
                )
                : $this->error('تم تجاوز الحد الأقصى لإعادة الإرسال. الرجاء المحاولة بعد ساعة.', [], 429);
        }

        $this->otpService->generateAndSend($request->email, $request->purpose);

        return $this->success([], 'تم إرسال كود جديد إلى بريدك الإلكتروني.');
    }
}
