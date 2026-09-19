<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_their_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => ['id' => $user->id, 'email' => $user->email],
            ]);
    }

    public function test_me_fails_without_a_token(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)->assertJson(['success' => false]);
    }

    public function test_me_fails_for_a_suspended_account(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_me_fails_when_email_verification_was_revoked_after_token_issuance(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Simulate an admin action nulling verification after the token was issued.
        $user->forceFill(['email_verified_at' => null])->save();

        $response = $this->getJson('/api/auth/me', ['Authorization' => "Bearer {$token}"]);

        $response->assertStatus(403)->assertJson(['success' => false]);
    }
}
