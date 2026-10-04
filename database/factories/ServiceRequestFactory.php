<?php

namespace Database\Factories;

use App\Models\Craftsman;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'craftsman_id' => Craftsman::factory(),
            'description' => fake()->sentence(),
            'status' => 'pending',
        ];
    }
}
