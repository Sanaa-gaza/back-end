<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['craftsman', 'institution', 'contractor']);

        return $this->success(new ProfileResource($user), '');
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        DB::transaction(function () use ($user, $data) {
            $user->update(array_intersect_key($data, array_flip([
                'full_name', 'governorate_id', 'area_id',
            ])));

            if ($user->role === 'craftsman' && $user->craftsman) {
                $user->craftsman->update(array_intersect_key($data, array_flip([
                    'bio', 'personal_photo_path', 'is_available',
                ])));
            }

            if ($user->role === 'institution' && $user->institution) {
                $user->institution->update(array_intersect_key($data, array_flip([
                    'description',
                ])));
            }

            if ($user->role === 'contractor' && $user->contractor) {
                $user->contractor->update(array_intersect_key($data, array_flip([
                    'description', 'main_specialization', 'workers_count',
                ])));
            }
        });

        $user->load(['craftsman', 'institution', 'contractor']);

        return $this->success(new ProfileResource($user), 'تم تحديث الملف الشخصي بنجاح.');
    }
}
