<?php

namespace Tests\Feature;

use App\Models\Craftsman;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_authenticated_user_can_request_a_service(): void
    {
        $customer = User::factory()->create();
        $craftsman = Craftsman::factory()->create();

        $response = $this->postJson('/api/service-requests', [
            'craftsman_id' => $craftsman->id,
            'description' => 'بحاجة لتصليح تمديدات كهربائية',
        ], $this->authHeaders($customer));

        $response->assertStatus(201)->assertJson(['success' => true, 'data' => ['status' => 'pending']]);
        $this->assertDatabaseHas('service_requests', [
            'customer_id' => $customer->id,
            'craftsman_id' => $craftsman->id,
            'status' => 'pending',
        ]);
    }

    public function test_request_fails_without_authentication(): void
    {
        $craftsman = Craftsman::factory()->create();

        $this->postJson('/api/service-requests', [
            'craftsman_id' => $craftsman->id,
            'description' => 'test',
        ])->assertStatus(401);
    }

    public function test_craftsman_can_accept_a_pending_request(): void
    {
        $craftsman = Craftsman::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['craftsman_id' => $craftsman->id]);

        $response = $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/accept",
            [],
            $this->authHeaders($craftsman->user)
        );

        $response->assertOk()->assertJson(['data' => ['status' => 'accepted']]);
    }

    public function test_other_craftsman_cannot_accept_a_request(): void
    {
        $craftsman = Craftsman::factory()->create();
        $otherCraftsman = Craftsman::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['craftsman_id' => $craftsman->id]);

        $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/accept",
            [],
            $this->authHeaders($otherCraftsman->user)
        )->assertStatus(403);
    }

    public function test_craftsman_can_reject_a_pending_request_with_a_reason(): void
    {
        $craftsman = Craftsman::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['craftsman_id' => $craftsman->id]);

        $response = $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/reject",
            ['reason' => 'غير متاح حالياً'],
            $this->authHeaders($craftsman->user)
        );

        $response->assertOk()->assertJson(['data' => ['status' => 'rejected']]);
    }

    public function test_craftsman_can_complete_an_accepted_request(): void
    {
        $craftsman = Craftsman::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create([
            'craftsman_id' => $craftsman->id,
            'status' => 'accepted',
        ]);

        $response = $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/complete",
            [],
            $this->authHeaders($craftsman->user)
        );

        $response->assertOk()->assertJson(['data' => ['status' => 'completed']]);
    }

    public function test_cannot_complete_a_request_that_was_never_accepted(): void
    {
        $craftsman = Craftsman::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create(['craftsman_id' => $craftsman->id]);

        $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/complete",
            [],
            $this->authHeaders($craftsman->user)
        )->assertStatus(422);
    }

    public function test_customer_can_review_a_completed_request_and_craftsman_rating_updates(): void
    {
        $craftsman = Craftsman::factory()->create(['avg_rating' => 0, 'reviews_count' => 0]);
        $customer = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create([
            'craftsman_id' => $craftsman->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        $response = $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/review",
            ['rating' => 5, 'comment' => 'ممتاز'],
            $this->authHeaders($customer)
        );

        $response->assertStatus(201)->assertJson(['data' => ['rating' => 5]]);
        $this->assertEquals('5.00', $craftsman->fresh()->avg_rating);
        $this->assertEquals(1, $craftsman->fresh()->reviews_count);
    }

    public function test_cannot_review_the_same_request_twice(): void
    {
        $craftsman = Craftsman::factory()->create();
        $customer = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create([
            'craftsman_id' => $craftsman->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
        ]);

        $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/review",
            ['rating' => 4],
            $this->authHeaders($customer)
        )->assertStatus(201);

        $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/review",
            ['rating' => 2],
            $this->authHeaders($customer)
        )->assertStatus(422);
    }

    public function test_cannot_review_a_request_that_is_not_completed(): void
    {
        $craftsman = Craftsman::factory()->create();
        $customer = User::factory()->create();
        $serviceRequest = ServiceRequest::factory()->create([
            'craftsman_id' => $craftsman->id,
            'customer_id' => $customer->id,
            'status' => 'pending',
        ]);

        $this->postJson(
            "/api/service-requests/{$serviceRequest->id}/review",
            ['rating' => 5],
            $this->authHeaders($customer)
        )->assertStatus(422);
    }

    public function test_craftsman_only_sees_requests_sent_to_them(): void
    {
        $craftsman = Craftsman::factory()->create();
        ServiceRequest::factory()->create(['craftsman_id' => $craftsman->id]);
        ServiceRequest::factory()->create();

        $response = $this->getJson('/api/service-requests', $this->authHeaders($craftsman->user));

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
