<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $crafts = [
            'سباك',
            'كهربائي',
            'نجار',
            'دهان',
            'حداد',
            'بلاط',
            'فني جبس',
            'فني ألمنيوم',
            'فني تكييف وتبريد',
            'ميكانيكي سيارات',
            'بستنة وتنسيق حدائق',
            'نقل عفش',
            'عامل نظافة',
        ];

        foreach ($crafts as $name) {
            Service::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
