<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            GovernorateSeeder::class,
            AreaSeeder::class,
            ServiceSeeder::class,
            AdminSeeder::class,
        ]);

        if (app()->environment('local')) {
            $area = Area::first();

            User::factory()->create([
                'full_name' => 'Test User',
                'email' => 'test@example.com',
                'governorate_id' => $area->governorate_id,
                'area_id' => $area->id,
            ]);
        }
    }
}
