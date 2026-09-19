<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterCraftsmanRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Models\Craftsman;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RegisterController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly OtpService $otpService) {}

    public function customer(RegisterCustomerRequest $request): JsonResponse
    {
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password,
            'role' => 'customer',
            'governorate_id' => $request->governorate_id,
            'area_id' => $request->area_id,
            'terms_accepted_at' => now(),
            'terms_version' => '1.0',
        ]);

        $this->otpService->generateAndSend($user->email, 'register');

        return $this->success(
            ['email' => $this->maskEmail($user->email)],
            'تم إنشاء الحساب بنجاح. تم إرسال رمز التحقق إلى بريدك الإلكتروني.',
            201
        );
    }

    public function craftsman(RegisterCraftsmanRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password,
                'role' => 'craftsman',
                'governorate_id' => $request->governorate_id,
                'area_id' => $request->area_id,
                'terms_accepted_at' => now(),
                'terms_version' => '1.0',
            ]);

            Craftsman::create([
                'user_id' => $user->id,
                'craft_id' => $request->craft_id,
                'personal_photo_path' => $request->personal_photo_path,
                'id_selfie_path' => $request->id_selfie_path,
                'bio' => $request->bio,
                'status' => 'pending',
            ]);

            return $user;
        });

        $this->otpService->generateAndSend($user->email, 'register');

        return $this->success(
            ['email' => $this->maskEmail($user->email)],
            'تم إنشاء الحساب بنجاح. تم إرسال رمز التحقق إلى بريدك الإلكتروني.',
            201
        );
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }
}
