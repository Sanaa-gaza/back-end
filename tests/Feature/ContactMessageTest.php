<?php

namespace Tests\Feature;

use App\Mail\ContactMessageReceivedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_guest_can_submit_the_contact_form_with_an_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', [
            'full_name' => 'أحمد خالد',
            'city' => 'غزة',
            'email' => 'ahmad@example.com',
            'subject' => 'general',
            'message' => 'استفسار عن الخدمات المتاحة',
        ]);

        $response->assertStatus(201)->assertJson(['success' => true]);
        $this->assertDatabaseHas('contact_messages', [
            'full_name' => 'أحمد خالد',
            'email' => 'ahmad@example.com',
            'user_id' => null,
        ]);
        Mail::assertSent(ContactMessageReceivedMail::class);
    }

    public function test_guest_submission_fails_without_an_email(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/contact', [
            'full_name' => 'أحمد خالد',
            'city' => 'غزة',
            'subject' => 'general',
            'message' => 'استفسار',
        ]);

        $response->assertStatus(422)->assertJson(['success' => false]);
        Mail::assertNothingSent();
    }

    public function test_authenticated_user_does_not_need_to_supply_an_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'user-account@example.com']);

        $response = $this->postJson('/api/contact', [
            'full_name' => 'أحمد خالد',
            'city' => 'غزة',
            'subject' => 'support',
            'message' => 'بحاجة لمساعدة',
        ], $this->authHeaders($user));

        $response->assertStatus(201);
        $this->assertDatabaseHas('contact_messages', [
            'user_id' => $user->id,
            'email' => 'user-account@example.com',
        ]);
    }

    public function test_invalid_subject_is_rejected(): void
    {
        Mail::fake();

        $this->postJson('/api/contact', [
            'full_name' => 'أحمد خالد',
            'city' => 'غزة',
            'email' => 'ahmad@example.com',
            'subject' => 'not-a-real-subject',
            'message' => 'استفسار',
        ])->assertStatus(422);
    }
}
