<?php

namespace Tests\Feature;

use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferenceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_governorates_list_is_returned(): void
    {
        Governorate::factory()->count(3)->create();

        $response = $this->getJson('/api/governorates');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_areas_for_a_governorate_are_returned(): void
    {
        $governorate = Governorate::factory()->create();
        Area::factory()->count(2)->for($governorate)->create();
        Area::factory()->count(4)->create();

        $response = $this->getJson("/api/governorates/{$governorate->id}/areas");

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_areas_for_a_nonexistent_governorate_returns_404(): void
    {
        $response = $this->getJson('/api/governorates/999/areas');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_services_list_only_returns_active_services(): void
    {
        Service::factory()->count(2)->create(['is_active' => true]);
        Service::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/services');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_services_list_includes_description_and_image(): void
    {
        Service::factory()->create([
            'is_active' => true,
            'description' => 'بناء وترميم بأعلى معايير الجودة',
            'image_path' => 'services/construction.jpg',
        ]);

        $response = $this->getJson('/api/services');

        $response->assertOk()
            ->assertJsonFragment([
                'description' => 'بناء وترميم بأعلى معايير الجودة',
                'image_path' => 'services/construction.jpg',
            ]);
    }

    public function test_activity_types_list_only_returns_active_types(): void
    {
        ActivityType::factory()->count(2)->create(['is_active' => true]);
        ActivityType::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/activity-types');

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }
}
