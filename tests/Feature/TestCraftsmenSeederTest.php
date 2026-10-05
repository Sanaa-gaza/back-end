<?php

namespace Tests\Feature;

use App\Models\Craftsman;
use App\Models\User;
use Database\Seeders\AreaSeeder;
use Database\Seeders\GovernorateSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\TestCraftsmenSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestCraftsmenSeederTest extends TestCase
{
    use RefreshDatabase;

    private function seedReferenceData(): void
    {
        $this->seed([GovernorateSeeder::class, AreaSeeder::class, ServiceSeeder::class]);
    }

    public function test_seeder_creates_approved_test_craftsmen(): void
    {
        $this->seedReferenceData();

        $this->seed(TestCraftsmenSeeder::class);

        $craftsmen = Craftsman::where('status', 'approved')->get();
        $this->assertCount(5, $craftsmen);
        $this->assertEquals(5, User::where('email', 'like', '%@test.senaa')->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seedReferenceData();

        $this->seed(TestCraftsmenSeeder::class);
        $this->seed(TestCraftsmenSeeder::class);

        $this->assertEquals(5, Craftsman::count());
    }

    public function test_seeded_craftsmen_appear_in_public_listing_and_featured(): void
    {
        $this->seedReferenceData();
        $this->seed(TestCraftsmenSeeder::class);

        $this->getJson('/api/craftsmen')->assertOk();
        $this->assertCount(5, $this->getJson('/api/craftsmen?trusted=1')->json('data.craftsmen'));
        $this->assertCount(5, $this->getJson('/api/craftsmen/featured')->json('data'));
    }
}
