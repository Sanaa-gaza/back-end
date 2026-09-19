<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    use ApiResponse;

    private const MAX_FAILED_ATTEMPTS = 5;

    private const LOCKOUT_MINUTES = 15;

    private const TOKEN_TTL_HOURS = 24;

    private const REMEMBER_TOKEN_TTL_DAYS = 30;

    private const GENERIC_INVALID_CREDENTIALS_MESSAGE = 'البريد الإلكتروني أو كلمة المرور غير صحيحة.';

    public function __construct(private readonly OtpService $otpService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->error(self::GENERIC_INVALID_CREDENTIALS_MESSAGE, [], 401);
        }

        if (! $user->email_verified_at) {
            $this->otpService->generateAndSend($user->email, 'register');

            return response()->json([
                'success' => false,
                'message' => 'الرجاء تأكيد بريدك الإلكتروني أولاً. تم إرسال كود تحقق جديد.',
                'errors' => [],
                'data' => [
                    'requires_verification' => true,
                    'email' => $user->email,
                ],
            ], 403);
        }

        if ($user->status === 'suspended') {
            return $this->error(
                'تم إيقاف هذا الحساب. السبب: '.($user->suspension_reason ?? 'غير محدد'),
                [],
                403
            );
        }

        if ($user->locked_until && $user->locked_until->isFuture()) {
            return $this->error('الحساب مقفل مؤقتاً بسبب محاولات دخول فاشلة متكررة. الرجاء المحاولة لاحقاً.', [], 423);
        }

        if (! Hash::check($request->password, $user->password)) {
            $attempts = $user->failed_attempts + 1;
            $locked = $attempts >= self::MAX_FAILED_ATTEMPTS;

            $user->forceFill([
                'failed_attempts' => $attempts,
                'locked_until' => $locked ? now()->addMinutes(self::LOCKOUT_MINUTES) : $user->locked_until,
            ])->save();

            return $this->error(self::GENERIC_INVALID_CREDENTIALS_MESSAGE, [], 401);
        }

        $user->forceFill([
            'failed_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ])->save();

        $expiresAt = $request->boolean('remember')
            ? now()->addDays(self::REMEMBER_TOKEN_TTL_DAYS)
            : now()->addHours(self::TOKEN_TTL_HOURS);

        $token = $user->createToken('auth-token', ['*'], $expiresAt)->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => new UserResource($user),
        ], 'تم تسجيل الدخول بنجاح.');
    }
}
