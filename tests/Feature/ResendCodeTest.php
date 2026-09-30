<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_code_sends_a_fresh_code(): void
    {
        Mail::fake();
        User::factory()->unverified()->create(['email' => 'resend1@example.com']);

        $response = $this->postJson('/api/auth/resend-code', [
            'email' => 'resend1@example.com',
            'purpose' => 'register',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        Mail::assertSent(OtpCodeMail::class);
        $this->assertDatabaseHas('otp_codes', ['email' => 'resend1@example.com', 'purpose' => 'register']);
    }

    public function test_resend_code_is_rejected_within_the_cooldown(): void
    {
        Mail::fake();
        User::factory()->unverified()->create(['email' => 'resend2@example.com']);

        $this->postJson('/api/auth/resend-code', ['email' => 'resend2@example.com', 'purpose' => 'register'])
            ->assertOk();

        $response = $this->postJson('/api/auth/resend-code', ['email' => 'resend2@example.com', 'purpose' => 'register']);

        $response->assertStatus(429)->assertJson(['success' => false]);
        $this->assertEquals(1, OtpCode::where('email', 'resend2@example.com')->count());
    }

    public function test_resend_code_is_rejected_after_three_sends_within_ten_minutes(): void
    {
        Mail::fake();
        User::factory()->unverified()->create(['email' => 'resend3@example.com']);

        // 3 prior sends within the last 10 minutes, each older than the 45s cooldown.
        foreach ([2, 4, 6] as $minutesAgo) {
            OtpCode::forceCreate([
                'email' => 'resend3@example.com',
                'purpose' => 'register',
                'code_hash' => hash('sha256', '111111'),
                'expires_at' => now()->addMinutes(3),
                'attempts' => 0,
                'consumed_at' => now(),
                'created_at' => now()->subMinutes($minutesAgo),
            ]);
        }

        $response = $this->postJson('/api/auth/resend-code', [
            'email' => 'resend3@example.com',
            'purpose' => 'register',
        ]);

        $response->assertStatus(429)->assertJson(['success' => false]);
        Mail::assertNotSent(OtpCodeMail::class);
    }

    public function test_resend_code_is_allowed_again_after_the_ten_minute_window_passes(): void
    {
        Mail::fake();
        User::factory()->unverified()->create(['email' => 'resend4@example.com']);

        // 3 prior sends, but all outside the 10-minute window.
        foreach ([11, 13, 15] as $minutesAgo) {
            OtpCode::forceCreate([
                'email' => 'resend4@example.com',
                'purpose' => 'register',
                'code_hash' => hash('sha256', '111111'),
                'expires_at' => now()->addMinutes(3),
                'attempts' => 0,
                'consumed_at' => now(),
                'created_at' => now()->subMinutes($minutesAgo),
            ]);
        }

        $response = $this->postJson('/api/auth/resend-code', [
            'email' => 'resend4@example.com',
            'purpose' => 'register',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        Mail::assertSent(OtpCodeMail::class);
    }

    public function test_resend_code_fails_validation_without_purpose(): void
    {
        $response = $this->postJson('/api/auth/resend-code', ['email' => 'someone@example.com']);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }
}
