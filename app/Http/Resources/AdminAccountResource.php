<?php

namespace App\Http\Resources;

use App\Models\Contractor;
use App\Models\Craftsman;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin-only view of a Craftsman/Institution/Contractor row, including
 * documents that are never exposed in any public-facing resource
 * (id_selfie_path, license_file_path).
 */
class AdminAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $base = [
            'account_type' => $this->accountType(),
            'account_id' => $this->id,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'verified_at' => $this->verified_at,
            'verified_by' => $this->verified_by,
            'user' => [
                'id' => $this->user->id,
                'full_name' => $this->user->full_name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
                'status' => $this->user->status,
                'governorate_id' => $this->user->governorate_id,
                'area_id' => $this->user->area_id,
                'national_id' => $this->user->national_id,
            ],
        ];

        return match (true) {
            $this->resource instanceof Craftsman => array_merge($base, [
                'craft_id' => $this->craft_id,
                'bio' => $this->bio,
                'personal_photo_path' => $this->personal_photo_path,
                'id_selfie_path' => $this->id_selfie_path,
                'avg_rating' => $this->avg_rating,
            ]),
            $this->resource instanceof Institution => array_merge($base, [
                'institution_name' => $this->institution_name,
                'commercial_registration_number' => $this->commercial_registration_number,
                'activity_type' => $this->activity_type,
                'license_file_path' => $this->license_file_path,
                'description' => $this->description,
            ]),
            $this->resource instanceof Contractor => array_merge($base, [
                'workers_count' => $this->workers_count,
                'main_specialization' => $this->main_specialization,
                'license_file_path' => $this->license_file_path,
                'description' => $this->description,
            ]),
            default => $base,
        };
    }

    private function accountType(): string
    {
        return match (true) {
            $this->resource instanceof Craftsman => 'craftsman',
            $this->resource instanceof Institution => 'institution',
            $this->resource instanceof Contractor => 'contractor',
            default => 'unknown',
        };
    }
}
