<?php

namespace Tests\Feature;

use App\Models\Contractor;
use App\Models\Craftsman;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPendingAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_admin_can_list_all_pending_accounts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Craftsman::factory()->create();
        Institution::factory()->create();
        Contractor::factory()->create();

        $response = $this->getJson('/api/admin/accounts/pending', $this->authHeaders($admin));

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_filter_pending_accounts_by_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Craftsman::factory()->create();
        Institution::factory()->create();

        $response = $this->getJson('/api/admin/accounts/pending?type=craftsman', $this->authHeaders($admin));

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertEquals('craftsman', $response->json('data.0.account_type'));
    }

    public function test_pending_accounts_fails_for_a_non_admin(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->getJson('/api/admin/accounts/pending', $this->authHeaders($customer));

        $response->assertStatus(403)->assertJson(['success' => false]);
    }

    public function test_pending_accounts_fails_without_a_token(): void
    {
        $this->getJson('/api/admin/accounts/pending')->assertStatus(401);
    }

    public function test_admin_can_view_a_single_craftsman_account_including_id_selfie(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $craftsman = Craftsman::factory()->create();

        $response = $this->getJson("/api/admin/accounts/craftsman/{$craftsman->id}", $this->authHeaders($admin));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'account_type' => 'craftsman',
                    'id_selfie_path' => $craftsman->id_selfie_path,
                ],
            ]);
    }

    public function test_show_account_fails_for_a_nonexistent_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->getJson('/api/admin/accounts/craftsman/999', $this->authHeaders($admin));

        $response->assertStatus(404)->assertJson(['success' => false]);
    }

    public function test_show_account_fails_for_an_invalid_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->getJson('/api/admin/accounts/bogus/1', $this->authHeaders($admin));

        $response->assertStatus(404)->assertJson(['success' => false]);
    }
}
