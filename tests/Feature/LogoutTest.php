<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/auth/logout', [], ['Authorization' => "Bearer {$token}"]);

        $response->assertOk()->assertJson(['success' => true]);

        // The row is gone from the database — this is the real proof of
        // revocation (a second live request in the same test process can't
        // reliably re-check auth here, since Sanctum/Auth caches the
        // resolved user for the test's container lifetime).
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_fails_without_a_token(): void
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401)->assertJson(['success' => false]);
    }
}
