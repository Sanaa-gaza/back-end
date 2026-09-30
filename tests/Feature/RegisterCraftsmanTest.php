<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisterCraftsmanTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        Storage::fake('public');

        $area = Area::factory()->for($governorate = Governorate::factory()->create())->create();
        $craft = Service::factory()->create();

        $photoPath = UploadedFile::fake()->image('photo.jpg')->store('uploads', 'public');
        $selfiePath = UploadedFile::fake()->image('selfie.jpg')->store('uploads', 'public');

        return array_merge([
            'full_name' => 'خالد يوسف',
            'email' => 'khaled@example.com',
            'phone' => '+970591234567',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'governorate_id' => $governorate->id,
            'area_id' => $area->id,
            'personal_photo_path' => $photoPath,
            'id_selfie_path' => $selfiePath,
            'craft_id' => $craft->id,
            'bio' => 'سباك بخبرة 10 سنوات.',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_craftsman_can_register_successfully(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'email' => 'new.craftsman@example.com',
        ]));

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => ['email' => 'n***@example.com'],
            ])
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('users', [
            'email' => 'new.craftsman@example.com',
            'role' => 'craftsman',
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('craftsmen', [
            'status' => 'pending',
        ]);

        Mail::assertSent(OtpCodeMail::class);
    }

    public function test_response_never_exposes_id_selfie_path(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'email' => 'no.leak@example.com',
        ]));

        $response->assertCreated();
        $this->assertStringNotContainsString('id_selfie_path', $response->getContent());
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'email' => 'taken@example.com',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_with_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '+970591234567']);

        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'email' => 'different@example.com',
            'phone' => '+970591234567',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('phone');
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'phone' => '0591234567',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('phone');
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('password');
    }

    public function test_registration_fails_when_personal_photo_was_not_uploaded_first(): void
    {
        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'personal_photo_path' => 'uploads/does-not-exist.jpg',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('personal_photo_path');
    }

    public function test_registration_fails_with_a_nonexistent_craft(): void
    {
        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'craft_id' => 999,
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('craft_id');
    }

    public function test_registration_fails_when_bio_exceeds_max_length(): void
    {
        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'bio' => str_repeat('a', 501),
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('bio');
    }

    public function test_registration_fails_without_bio(): void
    {
        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'bio' => '',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('bio');
    }

    public function test_registration_fails_with_a_too_short_bio(): void
    {
        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'bio' => 'قصير',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('bio');
    }

    public function test_craftsman_can_register_with_a_custom_craft_name(): void
    {
        Mail::fake();

        $payload = $this->validPayload(['email' => 'custom.craft@example.com']);
        unset($payload['craft_id']);
        $payload['custom_craft_name'] = 'نجار موبيليا';

        $response = $this->postJson('/api/auth/register/craftsman', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('services', ['name' => 'نجار موبيليا']);

        $craftId = Service::where('name', 'نجار موبيليا')->value('id');
        $this->assertDatabaseHas('craftsmen', ['craft_id' => $craftId]);
    }

    public function test_custom_craft_name_reuses_an_existing_normalized_match(): void
    {
        Mail::fake();

        $existing = Service::factory()->create(['name' => 'نجار']);

        $payload = $this->validPayload(['email' => 'reuse.craft@example.com']);
        unset($payload['craft_id']);
        $payload['custom_craft_name'] = '  نجّار  ';

        $response = $this->postJson('/api/auth/register/craftsman', $payload);

        $response->assertCreated();
        $this->assertDatabaseHas('craftsmen', ['craft_id' => $existing->id]);
        $this->assertEquals(1, Service::where('name', 'نجار')->count());
    }

    public function test_registration_fails_without_accepting_terms(): void
    {
        $response = $this->postJson('/api/auth/register/craftsman', $this->validPayload([
            'terms_accepted' => false,
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('terms_accepted');
    }
}
