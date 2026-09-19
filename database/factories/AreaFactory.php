<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Area>
 */
class AreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'name' => fake()->unique()->streetName(),
        ];
    }
}
