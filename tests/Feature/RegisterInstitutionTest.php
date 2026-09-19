<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\ActivityType;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegisterInstitutionTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        Storage::fake('public');

        $area = Area::factory()->for($governorate = Governorate::factory()->create())->create();
        $activityType = ActivityType::factory()->create();
        $licensePath = UploadedFile::fake()->create('license.pdf', 100, 'application/pdf')->store('uploads', 'public');

        return array_merge([
            'institution_name' => 'مؤسسة الأمل الخيرية',
            'commercial_registration_number' => 'CR-1234567',
            'email' => 'institution@example.com',
            'phone' => '+970591234567',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'governorate_id' => $governorate->id,
            'area_id' => $area->id,
            'activity_type_id' => $activityType->id,
            'license_file_path' => $licensePath,
            'description' => 'مؤسسة خيرية تعمل في قطاع غزة.',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_institution_can_register_successfully(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register/institution', $this->validPayload([
            'email' => 'new.institution@example.com',
        ]));

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => ['email' => 'n***@example.com'],
            ])
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('users', [
            'email' => 'new.institution@example.com',
            'role' => 'institution',
            'full_name' => 'مؤسسة الأمل الخيرية',
            'email_verified_at' => null,
        ]);

        $this->assertDatabaseHas('institutions', [
            'institution_name' => 'مؤسسة الأمل الخيرية',
            'status' => 'pending',
        ]);

        Mail::assertSent(OtpCodeMail::class);
    }

    public function test_registration_fails_with_duplicate_commercial_registration_number(): void
    {
        $user = User::factory()->create(['role' => 'institution']);
        Institution::factory()->for($user)->create(['commercial_registration_number' => 'CR-9999999']);

        $response = $this->postJson('/api/auth/register/institution', $this->validPayload([
            'email' => 'different@example.com',
            'commercial_registration_number' => 'CR-9999999',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('commercial_registration_number');
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/auth/register/institution', $this->validPayload([
            'email' => 'taken@example.com',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/auth/register/institution', $this->validPayload([
            'phone' => '0591234567',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('phone');
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register/institution', $this->validPayload([
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('password');
    }

    public function test_registration_fails_with_a_nonexistent_activity_type(): void
    {
        $response = $this->postJson('/api/auth/register/institution', $this->validPayload([
            'activity_type_id' => 999,
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('activity_type_id');
    }

    public function test_registration_fails_when_license_was_not_uploaded_first(): void
    {
        $response = $this->postJson('/api/auth/register/institution', $this->validPayload([
            'license_file_path' => 'uploads/does-not-exist.pdf',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('license_file_path');
    }

    public function test_registration_fails_without_accepting_terms(): void
    {
        $response = $this->postJson('/api/auth/register/institution', $this->validPayload([
            'terms_accepted' => false,
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('terms_accepted');
    }
}
