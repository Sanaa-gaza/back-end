<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectServiceRequestRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Resources\ReviewResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\Review;
use App\Models\ServiceRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceRequestController extends Controller
{
    use ApiResponse;

    /**
     * Any authenticated, non-admin user may request a service from a
     * craftsman — not only customers (confirmed with the team: an
     * institution or contractor can request a service too).
     */
    public function store(StoreServiceRequestRequest $request): JsonResponse
    {
        $serviceRequest = ServiceRequest::create([
            ...$request->validated(),
            'customer_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return $this->success(
            new ServiceRequestResource($serviceRequest->load(['customer', 'craftsman.user', 'service'])),
            'تم إرسال طلب الخدمة بنجاح.',
            201
        );
    }

    /**
     * Returns the authenticated user's own requests: as a customer if they
     * sent them, or as a craftsman if they received them.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ServiceRequest::query()->with(['customer', 'craftsman.user', 'service', 'review']);

        if ($user->craftsman) {
            $query->where('craftsman_id', $user->craftsman->id);
        } else {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $requests = $query->latest()->get();

        return $this->success(ServiceRequestResource::collection($requests));
    }

    public function accept(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        if (! $this->isOwningCraftsman($request, $serviceRequest)) {
            return $this->error('ليس لديك صلاحية على هذا الطلب.', [], 403);
        }

        if ($serviceRequest->status !== 'pending') {
            return $this->error('لا يمكن قبول هذا الطلب في حالته الحالية.', [], 422);
        }

        $serviceRequest->update(['status' => 'accepted']);

        return $this->success(
            new ServiceRequestResource($serviceRequest->load(['customer', 'craftsman.user', 'service'])),
            'تم قبول الطلب.'
        );
    }

    public function reject(RejectServiceRequestRequest $request, ServiceRequest $serviceRequest): JsonResponse
    {
        if (! $this->isOwningCraftsman($request, $serviceRequest)) {
            return $this->error('ليس لديك صلاحية على هذا الطلب.', [], 403);
        }

        if ($serviceRequest->status !== 'pending') {
            return $this->error('لا يمكن رفض هذا الطلب في حالته الحالية.', [], 422);
        }

        $serviceRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->validated('reason'),
        ]);

        return $this->success(
            new ServiceRequestResource($serviceRequest->load(['customer', 'craftsman.user', 'service'])),
            'تم رفض الطلب.'
        );
    }

    public function complete(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        if (! $this->isOwningCraftsman($request, $serviceRequest)) {
            return $this->error('ليس لديك صلاحية على هذا الطلب.', [], 403);
        }

        if ($serviceRequest->status !== 'accepted') {
            return $this->error('لا يمكن إنهاء هذا الطلب في حالته الحالية.', [], 422);
        }

        $serviceRequest->update(['status' => 'completed']);

        return $this->success(
            new ServiceRequestResource($serviceRequest->load(['customer', 'craftsman.user', 'service'])),
            'تم إنهاء الطلب بنجاح.'
        );
    }

    /**
     * Only the customer who sent the (now completed) request may review it,
     * once. The craftsman's avg_rating/reviews_count are recalculated from
     * all of their reviews inside the same transaction.
     */
    public function review(StoreReviewRequest $request, ServiceRequest $serviceRequest): JsonResponse
    {
        if ($serviceRequest->customer_id !== $request->user()->id) {
            return $this->error('ليس لديك صلاحية على هذا الطلب.', [], 403);
        }

        if ($serviceRequest->status !== 'completed') {
            return $this->error('لا يمكن تقييم طلب لم يكتمل بعد.', [], 422);
        }

        if ($serviceRequest->review()->exists()) {
            return $this->error('تم تقييم هذا الطلب مسبقاً.', [], 422);
        }

        $review = DB::transaction(function () use ($request, $serviceRequest) {
            $review = Review::create([
                ...$request->validated(),
                'service_request_id' => $serviceRequest->id,
                'customer_id' => $serviceRequest->customer_id,
                'craftsman_id' => $serviceRequest->craftsman_id,
            ]);

            $craftsman = $serviceRequest->craftsman;
            $craftsman->update([
                'avg_rating' => $craftsman->reviews()->avg('rating'),
                'reviews_count' => $craftsman->reviews()->count(),
            ]);

            return $review;
        });

        return $this->success(new ReviewResource($review->load('customer')), 'تم إرسال التقييم بنجاح.', 201);
    }

    private function isOwningCraftsman(Request $request, ServiceRequest $serviceRequest): bool
    {
        $craftsman = $request->user()->craftsman;

        return $craftsman && $craftsman->id === $serviceRequest->craftsman_id;
    }
}
