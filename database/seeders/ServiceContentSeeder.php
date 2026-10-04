<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * Fills in description/image_path for services by name. Images are
 * temporary placeholders (picsum.photos, seeded per service so each one
 * stays visually distinct) — swap image_path for real photos once the
 * design team provides them.
 */
class ServiceContentSeeder extends Seeder
{
    public function run(): void
    {
        $content = [
            'سباك' => [
                'description' => 'تصليح وتمديد شبكات المياه والصرف الصحي، وحل مشاكل التسريب والانسداد باحترافية.',
                'slug' => 'plumber',
            ],
            'كهربائي' => [
                'description' => 'تمديدات وصيانة الأنظمة الكهربائية بأمان وكفاءة لمنزلك أو منشأتك.',
                'slug' => 'electrician',
            ],
            'نجار' => [
                'description' => 'تصميم وتصنيع وتركيب الأثاث والأبواب الخشبية حسب الطلب.',
                'slug' => 'carpenter',
            ],
            'دهان' => [
                'description' => 'دهانات داخلية وخارجية بألوان وتشطيبات عالية الجودة.',
                'slug' => 'painter',
            ],
            'حداد' => [
                'description' => 'أعمال الحدادة والحماية المعدنية: أبواب وشبابيك ودرابزين.',
                'slug' => 'blacksmith',
            ],
            'بلاط' => [
                'description' => 'تركيب وتبليط الأرضيات والجدران بدقة وإتقان.',
                'slug' => 'tiling',
            ],
            'فني جبس' => [
                'description' => 'تصميم وتركيب أسقف وديكورات الجبس بورد.',
                'slug' => 'gypsum',
            ],
            'فني ألمنيوم' => [
                'description' => 'تصنيع وتركيب أبواب وشبابيك الألمنيوم والزجاج.',
                'slug' => 'aluminum',
            ],
            'فني تكييف وتبريد' => [
                'description' => 'تركيب وصيانة أجهزة التكييف والتبريد لمختلف الأحجام.',
                'slug' => 'ac-repair',
            ],
            'ميكانيكي سيارات' => [
                'description' => 'صيانة وإصلاح أعطال السيارات الميكانيكية والكهربائية.',
                'slug' => 'car-mechanic',
            ],
            'بستنة وتنسيق حدائق' => [
                'description' => 'تصميم وتنسيق وصيانة الحدائق والمساحات الخضراء.',
                'slug' => 'gardening',
            ],
            'نقل عفش' => [
                'description' => 'نقل الأثاث والعفش بأمان مع خدمات التغليف والفك والتركيب.',
                'slug' => 'moving',
            ],
            'عامل نظافة' => [
                'description' => 'تنظيف شامل للمنازل والمكاتب بأيدي محترفة.',
                'slug' => 'cleaning',
            ],
            'طباخ' => [
                'description' => 'إعداد وتحضير الوجبات والمناسبات بخبرة واحترافية.',
                'slug' => 'cooking',
            ],
        ];

        foreach ($content as $name => $data) {
            Service::where('name', $name)->update([
                'description' => $data['description'],
                'image_path' => "https://picsum.photos/seed/{$data['slug']}/600/400",
            ]);
        }
    }
}
