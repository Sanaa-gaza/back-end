<?php

namespace Database\Seeders;

use App\Models\Governorate;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            'شمال غزة' => ['بيت لاهيا', 'بيت حانون', 'جباليا', 'أم النصر'],
            'غزة' => ['الرمال', 'الشيخ رضوان', 'الزيتون', 'الدرج', 'التفاح', 'الشجاعية', 'الصبرة', 'تل الهوى', 'النصر'],
            'دير البلح' => ['دير البلح', 'النصيرات', 'البريج', 'المغازي', 'الزوايدة'],
            'خان يونس' => ['خان يونس', 'خزاعة', 'عبسان', 'بني سهيلا', 'القرارة'],
            'رفح' => ['رفح', 'الشوكة', 'تل السلطان', 'يبنا'],
        ];

        foreach ($areas as $governorateName => $areaNames) {
            $governorate = Governorate::where('name', $governorateName)->firstOrFail();

            foreach ($areaNames as $areaName) {
                $governorate->areas()->create(['name' => $areaName]);
            }
        }
    }
}
