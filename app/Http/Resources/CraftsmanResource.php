<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Public-facing craftsman card, used by the listing and featured endpoints.
 * Never exposes id_selfie_path or any other admin-only field.
 */
class CraftsmanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->user->full_name,
            'craft' => $this->whenLoaded('craft', fn () => [
                'id' => $this->craft->id,
                'name' => $this->craft->name,
            ]),
            'personal_photo_path' => $this->personal_photo_path,
            'bio' => $this->bio,
            'governorate_id' => $this->user->governorate_id,
            'area_id' => $this->user->area_id,
            'is_trusted' => $this->status === 'approved',
            'is_available' => $this->is_available,
            'avg_rating' => $this->avg_rating,
            'reviews_count' => $this->reviews_count,
        ];
    }
}
