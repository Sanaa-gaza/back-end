<?php

namespace Tests\Feature;

use App\Models\Craftsman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountActionsTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_admin_can_approve_a_pending_craftsman(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $craftsman = Craftsman::factory()->create(['status' => 'pending']);

        $response = $this->postJson(
            "/api/admin/accounts/craftsman/{$craftsman->id}/approve",
            [],
            $this->authHeaders($admin)
        );

        $response->assertOk()->assertJson(['success' => true]);

        $craftsman->refresh();
        $this->assertEquals('approved', $craftsman->status);
        $this->assertNotNull($craftsman->verified_at);
        $this->assertEquals($admin->id, $craftsman->verified_by);

        $this->assertDatabaseHas('account_reviews', [
            'account_type' => 'craftsman',
            'account_id' => $craftsman->id,
            'admin_id' => $admin->id,
            'decision' => 'approved',
        ]);
    }

    public function test_admin_can_reject_a_pending_craftsman_with_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $craftsman = Craftsman::factory()->create(['status' => 'pending']);

        $response = $this->postJson(
            "/api/admin/accounts/craftsman/{$craftsman->id}/reject",
            ['reason' => 'الوثائق غير واضحة'],
            $this->authHeaders($admin)
        );

        $response->assertOk()->assertJson(['success' => true]);

        $craftsman->refresh();
        $this->assertEquals('rejected', $craftsman->status);
        $this->assertEquals('الوثائق غير واضحة', $craftsman->rejection_reason);

        $this->assertDatabaseHas('account_reviews', [
            'account_type' => 'craftsman',
            'account_id' => $craftsman->id,
            'decision' => 'rejected',
            'reason' => 'الوثائق غير واضحة',
        ]);
    }

    public function test_reject_fails_without_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $craftsman = Craftsman::factory()->create(['status' => 'pending']);

        $response = $this->postJson(
            "/api/admin/accounts/craftsman/{$craftsman->id}/reject",
            [],
            $this->authHeaders($admin)
        );

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_admin_can_suspend_a_craftsman_account_and_revoke_its_tokens(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $craftsman = Craftsman::factory()->create();
        $craftsman->user->createToken('existing-session');

        $response = $this->postJson(
            "/api/admin/accounts/craftsman/{$craftsman->id}/suspend",
            ['reason' => 'بلاغات متكررة'],
            $this->authHeaders($admin)
        );

        $response->assertOk()->assertJson(['success' => true]);

        $craftsman->user->refresh();
        $this->assertEquals('suspended', $craftsman->user->status);
        $this->assertEquals('بلاغات متكررة', $craftsman->user->suspension_reason);
        $this->assertEquals(0, $craftsman->user->tokens()->count());

        $this->assertDatabaseHas('account_reviews', [
            'account_type' => 'craftsman',
            'account_id' => $craftsman->id,
            'decision' => 'suspended',
            'reason' => 'بلاغات متكررة',
        ]);
    }

    public function test_suspend_fails_without_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $craftsman = Craftsman::factory()->create();

        $response = $this->postJson(
            "/api/admin/accounts/craftsman/{$craftsman->id}/suspend",
            [],
            $this->authHeaders($admin)
        );

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_admin_can_activate_a_suspended_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $craftsman = Craftsman::factory()->create();
        $craftsman->user->forceFill([
            'status' => 'suspended',
            'suspension_reason' => 'بلاغات متكررة',
        ])->save();

        $response = $this->postJson(
            "/api/admin/accounts/craftsman/{$craftsman->id}/activate",
            [],
            $this->authHeaders($admin)
        );

        $response->assertOk()->assertJson(['success' => true]);

        $craftsman->user->refresh();
        $this->assertEquals('active', $craftsman->user->status);
        $this->assertNull($craftsman->user->suspension_reason);

        $this->assertDatabaseHas('account_reviews', [
            'account_type' => 'craftsman',
            'account_id' => $craftsman->id,
            'decision' => 'activated',
        ]);
    }

    public function test_account_actions_fail_for_a_non_admin(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $craftsman = Craftsman::factory()->create(['status' => 'pending']);

        $response = $this->postJson(
            "/api/admin/accounts/craftsman/{$craftsman->id}/approve",
            [],
            $this->authHeaders($customer)
        );

        $response->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_approve_fails_for_a_nonexistent_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->postJson(
            '/api/admin/accounts/craftsman/999/approve',
            [],
            $this->authHeaders($admin)
        );

        $response->assertStatus(404)->assertJson(['success' => false]);
    }
}
