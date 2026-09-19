<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use RefreshDatabase;

    private function sendCodeAndCapture(string $email, string $purpose): string
    {
        Mail::fake();

        app(OtpService::class)->generateAndSend($email, $purpose);

        $code = null;
        Mail::assertSent(OtpCodeMail::class, function (OtpCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    public function test_verify_email_succeeds_for_registration_and_issues_a_token(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'pending@example.com']);
        $code = $this->sendCodeAndCapture('pending@example.com', 'register');

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'pending@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $this->assertNotNull($user->fresh()->email_verified_at);

        $otp = OtpCode::where('email', 'pending@example.com')->first();
        $this->assertNotNull($otp->consumed_at);
    }

    public function test_verify_email_fails_with_wrong_code(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'pending2@example.com']);
        $this->sendCodeAndCapture('pending2@example.com', 'register');

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'pending2@example.com',
            'code' => '000000',
            'purpose' => 'register',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verify_email_fails_with_expired_code(): void
    {
        $user = User::factory()->unverified()->create(['email' => 'pending3@example.com']);
        $code = $this->sendCodeAndCapture('pending3@example.com', 'register');

        OtpCode::where('email', 'pending3@example.com')->update(['expires_at' => now()->subMinute()]);

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'pending3@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_verify_email_for_reset_purpose_returns_reset_token_not_login_token(): void
    {
        User::factory()->create(['email' => 'resetuser@example.com']);
        $code = $this->sendCodeAndCapture('resetuser@example.com', 'reset');

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'resetuser@example.com',
            'code' => $code,
            'purpose' => 'reset',
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['reset_token']])
            ->assertJsonMissingPath('data.token');
    }

    public function test_verify_email_fails_with_a_code_issued_for_a_different_purpose(): void
    {
        User::factory()->unverified()->create(['email' => 'crosspurpose@example.com']);
        $code = $this->sendCodeAndCapture('crosspurpose@example.com', 'reset');

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'crosspurpose@example.com',
            'code' => $code,
            'purpose' => 'register',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_a_new_code_invalidates_the_previous_one(): void
    {
        User::factory()->unverified()->create(['email' => 'resend@example.com']);
        $firstCode = $this->sendCodeAndCapture('resend@example.com', 'register');
        $this->sendCodeAndCapture('resend@example.com', 'register');

        $response = $this->postJson('/api/auth/verify-email', [
            'email' => 'resend@example.com',
            'code' => $firstCode,
            'purpose' => 'register',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }
}
