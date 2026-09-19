<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function obtainResetToken(User $user): string
    {
        Mail::fake();
        app(OtpService::class)->generateAndSend($user->email, 'reset');

        $code = null;
        Mail::assertSent(OtpCodeMail::class, function (OtpCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => $user->email,
            'code' => $code,
            'purpose' => 'reset',
        ]);

        return $response->json('data.reset_token');
    }

    public function test_reset_password_succeeds_with_a_valid_token(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1!')]);
        $token = $user->createToken('old-session')->plainTextToken;
        $resetToken = $this->obtainResetToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'reset_token' => $resetToken,
            'password' => 'NewStr0ng!Pass',
            'password_confirmation' => 'NewStr0ng!Pass',
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('NewStr0ng!Pass', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reset_password_fails_with_an_invalid_token(): void
    {
        $response = $this->postJson('/api/auth/reset-password', [
            'reset_token' => 'not-a-real-token',
            'password' => 'NewStr0ng!Pass',
            'password_confirmation' => 'NewStr0ng!Pass',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_reset_password_token_can_only_be_used_once(): void
    {
        $user = User::factory()->create();
        $resetToken = $this->obtainResetToken($user);

        $this->postJson('/api/auth/reset-password', [
            'reset_token' => $resetToken,
            'password' => 'NewStr0ng!Pass',
            'password_confirmation' => 'NewStr0ng!Pass',
        ])->assertOk();

        $response = $this->postJson('/api/auth/reset-password', [
            'reset_token' => $resetToken,
            'password' => 'AnotherPass1!',
            'password_confirmation' => 'AnotherPass1!',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_reset_password_fails_with_a_weak_password(): void
    {
        $user = User::factory()->create();
        $resetToken = $this->obtainResetToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'reset_token' => $resetToken,
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }
}
