<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Contractor;
use App\Models\Craftsman;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_customer_can_view_their_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson('/api/profile', $this->authHeaders($user));

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $user->id, 'email' => $user->email]]);
    }

    public function test_craftsman_profile_includes_role_specific_data(): void
    {
        $craftsman = Craftsman::factory()->create();
        $user = $craftsman->user;

        $response = $this->getJson('/api/profile', $this->authHeaders($user));

        $response->assertOk()
            ->assertJsonStructure(['data' => ['craftsman' => ['craft_id', 'bio', 'personal_photo_path', 'status']]])
            ->assertJsonMissingPath('data.craftsman.id_selfie_path');
    }

    public function test_profile_fails_without_a_token(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    public function test_user_can_update_editable_fields(): void
    {
        $user = User::factory()->create(['full_name' => 'Old Name']);
        $newArea = Area::factory()->for($newGovernorate = Governorate::factory()->create())->create();

        $response = $this->putJson('/api/profile', [
            'full_name' => 'New Name',
            'governorate_id' => $newGovernorate->id,
            'area_id' => $newArea->id,
        ], $this->authHeaders($user));

        $response->assertOk()
            ->assertJson(['success' => true, 'data' => ['full_name' => 'New Name']]);

        $this->assertEquals('New Name', $user->fresh()->full_name);
        $this->assertEquals($newGovernorate->id, $user->fresh()->governorate_id);
    }

    public function test_national_id_cannot_be_changed_via_profile(): void
    {
        $contractor = Contractor::factory()->create();
        $user = $contractor->user;
        $originalNationalId = $user->national_id;

        $response = $this->putJson('/api/profile', [
            'national_id' => '111111111',
            'full_name' => 'Updated Contractor Name',
        ], $this->authHeaders($user));

        $response->assertOk();
        $this->assertEquals($originalNationalId, $user->fresh()->national_id);
        $this->assertEquals('Updated Contractor Name', $user->fresh()->full_name);
    }

    public function test_craft_id_cannot_be_changed_via_profile(): void
    {
        $craftsman = Craftsman::factory()->create();
        $user = $craftsman->user;
        $originalCraftId = $craftsman->craft_id;

        $response = $this->putJson('/api/profile', [
            'craft_id' => 999,
            'bio' => 'Updated bio',
        ], $this->authHeaders($user));

        $response->assertOk();
        $this->assertEquals($originalCraftId, $craftsman->fresh()->craft_id);
        $this->assertEquals('Updated bio', $craftsman->fresh()->bio);
    }

    public function test_craftsman_can_update_personal_photo_after_uploading_it(): void
    {
        Storage::fake('public');
        $craftsman = Craftsman::factory()->create();
        $user = $craftsman->user;
        $newPhotoPath = UploadedFile::fake()->image('new-photo.jpg')->store('uploads', 'public');

        $response = $this->putJson('/api/profile', [
            'personal_photo_path' => $newPhotoPath,
        ], $this->authHeaders($user));

        $response->assertOk();
        $this->assertEquals($newPhotoPath, $craftsman->fresh()->personal_photo_path);
    }

    public function test_update_fails_when_photo_was_not_uploaded_first(): void
    {
        $craftsman = Craftsman::factory()->create();
        $user = $craftsman->user;

        $response = $this->putJson('/api/profile', [
            'personal_photo_path' => 'uploads/does-not-exist.jpg',
        ], $this->authHeaders($user));

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_update_fails_when_area_does_not_belong_to_governorate(): void
    {
        $user = User::factory()->create();
        $otherGovernorate = Governorate::factory()->create();

        $response = $this->putJson('/api/profile', [
            'governorate_id' => $otherGovernorate->id,
            'area_id' => $user->area_id,
        ], $this->authHeaders($user));

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_contractor_can_update_business_fields(): void
    {
        $contractor = Contractor::factory()->create(['workers_count' => 5]);
        $user = $contractor->user;

        $response = $this->putJson('/api/profile', [
            'workers_count' => 25,
            'main_specialization' => 'Electrical work',
            'description' => 'Updated description',
        ], $this->authHeaders($user));

        $response->assertOk();
        $contractor->refresh();
        $this->assertEquals(25, $contractor->workers_count);
        $this->assertEquals('Electrical work', $contractor->main_specialization);
    }
}
