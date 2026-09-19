<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminAccountResource;
use App\Models\Contractor;
use App\Models\Craftsman;
use App\Models\Institution;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAccountController extends Controller
{
    use ApiResponse;

    private const TYPE_MODELS = [
        'craftsman' => Craftsman::class,
        'institution' => Institution::class,
        'contractor' => Contractor::class,
    ];

    public function pending(Request $request): JsonResponse
    {
        $type = $request->query('type');

        if ($type && ! isset(self::TYPE_MODELS[$type])) {
            return $this->error('نوع الحساب غير صحيح.', [], 422);
        }

        $types = $type ? [$type] : array_keys(self::TYPE_MODELS);

        $accounts = collect($types)
            ->flatMap(fn (string $t) => self::TYPE_MODELS[$t]::with('user')
                ->where('status', 'pending')
                ->get())
            ->values();

        return $this->success(AdminAccountResource::collection($accounts));
    }

    public function show(string $type, int $id): JsonResponse
    {
        $model = $this->resolveAccount($type, $id);

        if (! $model) {
            return $this->error('نوع الحساب غير صحيح، أو الحساب غير موجود.', [], 404);
        }

        return $this->success(new AdminAccountResource($model->load('user')));
    }

    private function resolveAccount(string $type, int $id): ?Model
    {
        if (! isset(self::TYPE_MODELS[$type])) {
            return null;
        }

        return self::TYPE_MODELS[$type]::find($id);
    }
}
