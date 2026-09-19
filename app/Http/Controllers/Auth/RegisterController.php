<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterContractorRequest;
use App\Http\Requests\RegisterCraftsmanRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\RegisterInstitutionRequest;
use App\Models\Contractor;
use App\Models\Craftsman;
use App\Models\Institution;
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

    public function institution(RegisterInstitutionRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'full_name' => $request->institution_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password,
                'role' => 'institution',
                'governorate_id' => $request->governorate_id,
                'area_id' => $request->area_id,
                'terms_accepted_at' => now(),
                'terms_version' => '1.0',
            ]);

            Institution::create([
                'user_id' => $user->id,
                'institution_name' => $request->institution_name,
                'commercial_registration_number' => $request->commercial_registration_number,
                'activity_type_id' => $request->activity_type_id,
                'license_file_path' => $request->license_file_path,
                'description' => $request->description,
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

    public function contractor(RegisterContractorRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password,
                'role' => 'contractor',
                'national_id' => $request->national_id,
                'governorate_id' => $request->governorate_id,
                'area_id' => $request->area_id,
                'terms_accepted_at' => now(),
                'terms_version' => '1.0',
            ]);

            Contractor::create([
                'user_id' => $user->id,
                'workers_count' => $request->workers_count,
                'main_specialization' => $request->main_specialization,
                'license_file_path' => $request->license_file_path,
                'description' => $request->description,
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
