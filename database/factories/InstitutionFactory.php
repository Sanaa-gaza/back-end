<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
class InstitutionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'institution']),
            'institution_name' => fake()->company(),
            'commercial_registration_number' => fake()->unique()->numerify('CR-#######'),
            'activity_type' => fake()->randomElement(['إغاثة إنسانية', 'تعليم', 'صحة', 'تنمية مجتمعية']),
            'license_file_path' => 'institutions/licenses/'.fake()->uuid().'.pdf',
            'description' => fake()->text(200),
            'status' => 'pending',
        ];
    }
}
