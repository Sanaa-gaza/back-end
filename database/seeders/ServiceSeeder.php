<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['سباك', 'كهربائي', 'فني تكييف'] as $name) {
            Service::create(['name' => $name, 'is_active' => true]);
        }
    }
}
