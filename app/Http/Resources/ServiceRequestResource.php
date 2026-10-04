<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer' => [
                'id' => $this->customer->id,
                'full_name' => $this->customer->full_name,
            ],
            'craftsman' => [
                'id' => $this->craftsman->id,
                'full_name' => $this->craftsman->user->full_name,
            ],
            'service' => $this->whenLoaded('service', fn () => $this->service ? [
                'id' => $this->service->id,
                'name' => $this->service->name,
            ] : null),
            'description' => $this->description,
            'preferred_date' => $this->preferred_date,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'reviewed' => $this->whenLoaded('review', fn () => $this->review !== null),
            'created_at' => $this->created_at,
        ];
    }
}
