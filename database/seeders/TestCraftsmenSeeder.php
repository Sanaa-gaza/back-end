<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Craftsman;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Test-only approved craftsmen for frontend integration. NOT part of
 * DatabaseSeeder (that runs on every deploy). Run manually:
 *   php artisan db:seed --class=TestCraftsmenSeeder --force
 * Remove later with: users where email like '%@test.senaa'.
 */
class TestCraftsmenSeeder extends Seeder
{
    private const PASSWORD = 'Test@12345';

    public function run(): void
    {
        $areas = Area::orderBy('id')->get();
        $services = Service::where('is_active', true)->orderBy('id')->get();

        if ($areas->isEmpty() || $services->isEmpty()) {
            $this->command?->warn('Areas or services are missing. Run the main seeders first.');

            return;
        }

        $profiles = [
            ['name' => 'أحمد السباك', 'bio' => 'سباك بخبرة في تصليح الأنابيب وحل مشاكل التسريب.', 'rating' => 4.8, 'reviews' => 42],
            ['name' => 'خالد النجار', 'bio' => 'نجار متخصص بتصنيع الأثاث والأبواب الخشبية.', 'rating' => 4.5, 'reviews' => 28],
            ['name' => 'يوسف الدهان', 'bio' => 'دهانات داخلية وخارجية بتشطيب عالي الجودة.', 'rating' => 4.2, 'reviews' => 15],
            ['name' => 'سامر الكهربائي', 'bio' => 'تمديدات وصيانة كهربائية بأمان وكفاءة.', 'rating' => 4.9, 'reviews' => 63],
            ['name' => 'عمر فني التكييف', 'bio' => 'تركيب وصيانة أجهزة التكييف والتبريد.', 'rating' => 3.9, 'reviews' => 9],
        ];

        foreach ($profiles as $index => $profile) {
            $email = 'test.craftsman'.($index + 1).'@test.senaa';

            if (User::where('email', $email)->exists()) {
                continue;
            }

            $area = $areas[$index % $areas->count()];
            $service = $services[$index % $services->count()];

            $user = User::create([
                'full_name' => $profile['name'],
                'email' => $email,
                'phone' => '+97059900000'.($index + 1),
                'password' => Hash::make(self::PASSWORD),
                'role' => 'craftsman',
                'governorate_id' => $area->governorate_id,
                'area_id' => $area->id,
                'terms_accepted_at' => now(),
                'terms_version' => '1.0',
                'email_verified_at' => now(),
            ]);

            Craftsman::create([
                'user_id' => $user->id,
                'craft_id' => $service->id,
                'personal_photo_path' => 'uploads/test-placeholder.png',
                'id_selfie_path' => 'uploads/test-placeholder.png',
                'bio' => $profile['bio'],
                'status' => 'approved',
                'verified_at' => now(),
                'avg_rating' => $profile['rating'],
                'reviews_count' => $profile['reviews'],
                'is_available' => true,
            ]);
        }
    }
}
