<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityTypeResource;
use App\Http\Resources\AreaResource;
use App\Http\Resources\GovernorateResource;
use App\Http\Resources\ServiceResource;
use App\Models\ActivityType;
use App\Models\Governorate;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReferenceDataController extends Controller
{
    use ApiResponse;

    public function governorates(): JsonResponse
    {
        return $this->success(
            GovernorateResource::collection(Governorate::orderBy('name')->get())
        );
    }

    public function areas(int $governorate): JsonResponse
    {
        $governorate = Governorate::find($governorate);

        if (! $governorate) {
            return $this->error('المحافظة غير موجودة.', status: 404);
        }

        return $this->success(
            AreaResource::collection($governorate->areas()->orderBy('name')->get())
        );
    }

    public function services(): JsonResponse
    {
        return $this->success(
            ServiceResource::collection(Service::where('is_active', true)->orderBy('name')->get())
        );
    }

    public function activityTypes(): JsonResponse
    {
        return $this->success(
            ActivityTypeResource::collection(ActivityType::where('is_active', true)->orderBy('name')->get())
        );
    }
}
