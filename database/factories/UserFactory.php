<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+970'.fake()->randomElement(['59', '56']).fake()->unique()->numerify('#######'),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'customer',
            'governorate_id' => Governorate::factory(),
            'area_id' => Area::factory(),
            'terms_accepted_at' => now(),
            'terms_version' => '1.0',
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
