<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_code_for_a_registered_email(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/forgot-password', ['email' => $user->email]);

        $response->assertOk()->assertJson(['success' => true]);
        Mail::assertSent(OtpCodeMail::class);
        $this->assertDatabaseHas('otp_codes', ['email' => $user->email, 'purpose' => 'reset']);
    }

    public function test_forgot_password_returns_the_same_neutral_message_for_an_unregistered_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'إذا كان البريد الإلكتروني مسجلاً لدينا، سيصلك كود لاستعادة كلمة المرور.',
            ]);

        Mail::assertNotSent(OtpCodeMail::class);
    }

    public function test_forgot_password_fails_validation_without_an_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', []);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }
}
