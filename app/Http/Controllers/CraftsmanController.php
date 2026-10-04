<?php

namespace App\Http\Controllers;

use App\Http\Resources\CraftsmanResource;
use App\Models\Craftsman;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CraftsmanController extends Controller
{
    use ApiResponse;

    private const PER_PAGE = 12;

    private const FEATURED_LIMIT = 10;

    /**
     * Public listing used by the "الحرفيين" page: filterable by craft
     * (service_id), area, a minimum rating threshold, a free-text search
     * over the craftsman's name/craft, and a "موثوق" (trusted-only) toggle.
     * Rejected craftsmen and suspended users never appear.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Craftsman::query()
            ->with(['user', 'craft'])
            ->whereIn('status', ['pending', 'approved'])
            ->whereHas('user', fn ($q) => $q->where('status', '!=', 'suspended'));

        if ($request->filled('service_id')) {
            $query->where('craft_id', $request->query('service_id'));
        }

        if ($request->filled('area_id')) {
            $query->whereHas('user', fn ($q) => $q->where('area_id', $request->query('area_id')));
        }

        if ($request->filled('governorate_id')) {
            $query->whereHas('user', fn ($q) => $q->where('governorate_id', $request->query('governorate_id')));
        }

        if ($request->filled('min_rating')) {
            $query->where('avg_rating', '>=', $request->query('min_rating'));
        }

        if ($request->boolean('trusted')) {
            $query->where('status', 'approved');
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', fn ($uq) => $uq->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('craft', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        $paginator = $query->orderByDesc('avg_rating')->paginate(self::PER_PAGE);

        return $this->success([
            'craftsmen' => CraftsmanResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * "حرفيو الشهر المتميزون": the top 10 trusted (admin-approved) craftsmen
     * ranked by average rating.
     */
    public function featured(): JsonResponse
    {
        $craftsmen = Craftsman::query()
            ->with(['user', 'craft'])
            ->where('status', 'approved')
            ->whereHas('user', fn ($q) => $q->where('status', '!=', 'suspended'))
            ->orderByDesc('avg_rating')
            ->orderByDesc('reviews_count')
            ->limit(self::FEATURED_LIMIT)
            ->get();

        return $this->success(CraftsmanResource::collection($craftsmen));
    }
}
