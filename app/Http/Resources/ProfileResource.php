<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'national_id' => $this->national_id,
            'governorate_id' => $this->governorate_id,
            'area_id' => $this->area_id,
            'email_verified_at' => $this->email_verified_at,

            'craftsman' => $this->when($this->role === 'craftsman' && $this->craftsman, fn () => [
                'craft_id' => $this->craftsman->craft_id,
                'bio' => $this->craftsman->bio,
                'personal_photo_path' => $this->craftsman->personal_photo_path,
                'status' => $this->craftsman->status,
                'avg_rating' => $this->craftsman->avg_rating,
            ]),

            'institution' => $this->when($this->role === 'institution' && $this->institution, fn () => [
                'institution_name' => $this->institution->institution_name,
                'commercial_registration_number' => $this->institution->commercial_registration_number,
                'activity_type_id' => $this->institution->activity_type_id,
                'description' => $this->institution->description,
                'status' => $this->institution->status,
            ]),

            'contractor' => $this->when($this->role === 'contractor' && $this->contractor, fn () => [
                'workers_count' => $this->contractor->workers_count,
                'main_specialization' => $this->contractor->main_specialization,
                'description' => $this->contractor->description,
                'status' => $this->contractor->status,
            ]),
        ];
    }
}
