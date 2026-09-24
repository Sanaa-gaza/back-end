<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class GovernorateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['شمال غزة', 'غزة', 'دير البلح', 'خان يونس', 'رفح'] as $name) {
            Governorate::firstOrCreate(['name' => $name]);
        }
    }
}
