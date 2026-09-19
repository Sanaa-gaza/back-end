<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('role', 'admin')->exists()) {
            return;
        }

        $area = Area::firstOrFail();

        User::create([
            'full_name' => 'Senaa Admin',
            'email' => 'admin@senaa.test',
            'phone' => '+970590000000',
            'password' => 'Admin@12345',
            'role' => 'admin',
            'governorate_id' => $area->governorate_id,
            'area_id' => $area->id,
            'terms_accepted_at' => now(),
            'terms_version' => '1.0',
            'email_verified_at' => now(),
        ]);
    }
}
