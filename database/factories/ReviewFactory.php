<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ServiceRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $serviceRequest = ServiceRequest::factory()->create(['status' => 'completed']);

        return [
            'service_request_id' => $serviceRequest->id,
            'customer_id' => $serviceRequest->customer_id,
            'craftsman_id' => $serviceRequest->craftsman_id,
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->optional()->sentence(),
        ];
    }
}
