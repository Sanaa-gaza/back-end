<?php

namespace Database\Factories;

use App\Models\Contractor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contractor>
 */
class ContractorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => 'contractor',
                'national_id' => fake()->unique()->numerify('#########'),
            ]),
            'workers_count' => fake()->numberBetween(1, 50),
            'main_specialization' => fake()->jobTitle(),
            'license_file_path' => 'contractors/licenses/'.fake()->uuid().'.pdf',
            'description' => fake()->optional()->text(200),
            'status' => 'pending',
        ];
    }
}
