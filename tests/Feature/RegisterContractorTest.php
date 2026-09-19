<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisterContractorTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        Storage::fake('public');

        $area = Area::factory()->for($governorate = Governorate::factory()->create())->create();
        $licensePath = UploadedFile::fake()->create('license.pdf', 100, 'application/pdf')->store('uploads', 'public');

        return array_merge([
            'full_name' => 'أحمد محمود سالم عبدالله',
            'national_id' => '123456789',
            'email' => 'contractor@example.com',
            'phone' => '+970591234567',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'governorate_id' => $governorate->id,
            'area_id' => $area->id,
            'workers_count' => 15,
            'main_specialization' => 'بناء وتشييد',
            'description' => 'مقاول بخبرة واسعة.',
            'license_file_path' => $licensePath,
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_contractor_can_register_successfully(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'email' => 'new.contractor@example.com',
        ]));

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => ['email' => 'n***@example.com'],
            ])
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('users', [
            'email' => 'new.contractor@example.com',
            'role' => 'contractor',
            'national_id' => '123456789',
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('contractors', [
            'workers_count' => 15,
            'status' => 'pending',
        ]);

        Mail::assertSent(OtpCodeMail::class);
    }

    public function test_registration_fails_with_duplicate_national_id(): void
    {
        User::factory()->create(['national_id' => '999999999']);

        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'email' => 'different@example.com',
            'national_id' => '999999999',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('national_id');
    }

    public function test_registration_fails_with_invalid_national_id_length(): void
    {
        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'national_id' => '12345',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('national_id');
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'email' => 'taken@example.com',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'phone' => '0591234567',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('phone');
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('password');
    }

    public function test_registration_fails_with_zero_workers_count(): void
    {
        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'workers_count' => 0,
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('workers_count');
    }

    public function test_registration_fails_when_license_was_not_uploaded_first(): void
    {
        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'license_file_path' => 'uploads/does-not-exist.pdf',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('license_file_path');
    }

    public function test_registration_fails_without_accepting_terms(): void
    {
        $response = $this->postJson('/api/auth/register/contractor', $this->validPayload([
            'terms_accepted' => false,
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('terms_accepted');
    }
}
