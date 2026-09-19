<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\Area;
use App\Models\Governorate;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterCustomerTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        $area = Area::factory()->for($governorate = Governorate::factory()->create())->create();

        return array_merge([
            'full_name' => 'محمد أحمد',
            'email' => 'mohammed@example.com',
            'phone' => '+970591234567',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'governorate_id' => $governorate->id,
            'area_id' => $area->id,
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_customer_can_register_successfully(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/register/customer', $this->validPayload([
            'email' => 'new.customer@example.com',
        ]));

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'data' => ['email' => 'n***@example.com'],
            ])
            ->assertJsonMissingPath('data.token');

        $this->assertDatabaseHas('users', [
            'email' => 'new.customer@example.com',
            'role' => 'customer',
            'email_verified_at' => null,
        ]);

        $otp = OtpCode::where('email', 'new.customer@example.com')->where('purpose', 'register')->first();
        $this->assertNotNull($otp);
        $this->assertNotEquals('123456', $otp->code_hash); // stored hashed, not plaintext

        Mail::assertSent(OtpCodeMail::class);
    }

    public function test_registration_normalizes_a_972_phone_to_970(): void
    {
        Mail::fake();

        $this->postJson('/api/auth/register/customer', $this->validPayload([
            'email' => 'other@example.com',
            'phone' => '+972591234567',
        ]))->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'other@example.com',
            'phone' => '+970591234567',
        ]);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/auth/register/customer', $this->validPayload([
            'email' => 'taken@example.com',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('email');
    }

    public function test_registration_fails_with_duplicate_phone(): void
    {
        User::factory()->create(['phone' => '+970591234567']);

        $response = $this->postJson('/api/auth/register/customer', $this->validPayload([
            'email' => 'different@example.com',
            'phone' => '+970591234567',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('phone');
    }

    public function test_registration_fails_with_invalid_phone_format(): void
    {
        $response = $this->postJson('/api/auth/register/customer', $this->validPayload([
            'phone' => '0591234567',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('phone');
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/auth/register/customer', $this->validPayload([
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('password');
    }

    public function test_registration_fails_when_area_does_not_belong_to_governorate(): void
    {
        $otherGovernorate = Governorate::factory()->create();

        $response = $this->postJson('/api/auth/register/customer', $this->validPayload([
            'governorate_id' => $otherGovernorate->id,
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('area_id');
    }

    public function test_registration_fails_without_accepting_terms(): void
    {
        $response = $this->postJson('/api/auth/register/customer', $this->validPayload([
            'terms_accepted' => false,
        ]));

        $response->assertStatus(422)
            ->assertJson(['success' => false])
            ->assertJsonValidationErrors('terms_accepted');
    }
}
