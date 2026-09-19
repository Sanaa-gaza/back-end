<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Str0ng!Pass')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Str0ng!Pass',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertEquals(0, $user->fresh()->failed_attempts);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_fails_with_wrong_password_using_a_generic_message(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Str0ng!Pass')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'WrongPass1!',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.']);

        $this->assertEquals(1, $user->fresh()->failed_attempts);
    }

    public function test_login_fails_for_a_nonexistent_email_with_the_same_generic_message(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'WrongPass1!',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة.']);
    }

    public function test_account_locks_after_five_failed_attempts(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Str0ng!Pass')]);

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'WrongPass1!',
            ]);
        }

        $this->assertEquals(5, $user->fresh()->failed_attempts);
        $this->assertNotNull($user->fresh()->locked_until);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Str0ng!Pass', // even the correct password is rejected while locked
        ]);

        $response->assertStatus(423)->assertJson(['success' => false]);
    }

    public function test_login_fails_for_unverified_email_and_sends_a_new_code(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create(['password' => Hash::make('Str0ng!Pass')]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Str0ng!Pass',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'data' => ['requires_verification' => true],
            ]);

        Mail::assertSent(OtpCodeMail::class);
        $this->assertDatabaseHas('otp_codes', ['email' => $user->email, 'purpose' => 'register']);
    }

    public function test_login_fails_for_a_suspended_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Str0ng!Pass'),
            'status' => 'suspended',
            'suspension_reason' => 'مخالفة الشروط',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Str0ng!Pass',
        ]);

        $response->assertStatus(403)
            ->assertJson(['success' => false]);

        $this->assertStringContainsString('مخالفة الشروط', $response->json('message'));
    }

    public function test_remember_issues_a_longer_lived_token_than_default(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Str0ng!Pass')]);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'Str0ng!Pass',
            'remember' => true,
        ])->assertOk();

        $token = $user->fresh()->tokens()->latest()->first();
        $this->assertNotNull($token->expires_at);
        $this->assertTrue(now()->diffInDays($token->expires_at) > 20);
    }
}
