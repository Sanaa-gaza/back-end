<?php

namespace Database\Factories;

use App\Models\Craftsman;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Craftsman>
 */
class CraftsmanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'craftsman']),
            'craft_id' => Service::factory(),
            'personal_photo_path' => 'craftsmen/photos/'.fake()->uuid().'.jpg',
            'id_selfie_path' => 'craftsmen/id-selfies/'.fake()->uuid().'.jpg',
            'bio' => fake()->text(200),
            'status' => 'pending',
        ];
    }
}
