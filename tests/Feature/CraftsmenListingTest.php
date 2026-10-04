<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Craftsman;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CraftsmenListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_is_public_and_returns_craftsmen(): void
    {
        Craftsman::factory()->count(3)->create();

        $response = $this->getJson('/api/craftsmen');

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertCount(3, $response->json('data.craftsmen'));
    }

    public function test_listing_can_be_filtered_by_service(): void
    {
        $plumbing = Service::factory()->create();
        $electrical = Service::factory()->create();

        Craftsman::factory()->create(['craft_id' => $plumbing->id]);
        Craftsman::factory()->create(['craft_id' => $electrical->id]);

        $response = $this->getJson("/api/craftsmen?service_id={$plumbing->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data.craftsmen'));
    }

    public function test_listing_can_be_filtered_by_minimum_rating(): void
    {
        Craftsman::factory()->create(['avg_rating' => 4.8]);
        Craftsman::factory()->create(['avg_rating' => 2.0]);

        $response = $this->getJson('/api/craftsmen?min_rating=4');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.craftsmen'));
    }

    public function test_listing_can_be_filtered_by_area(): void
    {
        $area = Area::factory()->create();
        $craftsman = Craftsman::factory()->create();
        $craftsman->user->update(['area_id' => $area->id]);

        Craftsman::factory()->create();

        $response = $this->getJson("/api/craftsmen?area_id={$area->id}");

        $response->assertOk();
        $this->assertCount(1, $response->json('data.craftsmen'));
    }

    public function test_trusted_filter_only_returns_approved_craftsmen(): void
    {
        Craftsman::factory()->create(['status' => 'approved']);
        Craftsman::factory()->create(['status' => 'pending']);

        $response = $this->getJson('/api/craftsmen?trusted=1');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.craftsmen'));
    }

    public function test_rejected_craftsmen_never_appear(): void
    {
        Craftsman::factory()->create(['status' => 'rejected']);

        $response = $this->getJson('/api/craftsmen');

        $response->assertOk();
        $this->assertCount(0, $response->json('data.craftsmen'));
    }

    public function test_featured_returns_top_ten_approved_craftsmen_by_rating(): void
    {
        Craftsman::factory()->count(12)->create(['status' => 'approved'])
            ->each(fn (Craftsman $c, int $i) => $c->update(['avg_rating' => $i]));

        Craftsman::factory()->create(['status' => 'pending', 'avg_rating' => 5]);

        $response = $this->getJson('/api/craftsmen/featured');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(10, $data);
        $this->assertEquals('11.00', $data[0]['avg_rating']);
    }
}
