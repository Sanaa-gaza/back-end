<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectAccountRequest;
use App\Http\Requests\Admin\SuspendAccountRequest;
use App\Http\Resources\AdminAccountResource;
use App\Models\AccountReview;
use App\Models\Contractor;
use App\Models\Craftsman;
use App\Models\Institution;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function approve(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->resolveAccount($type, $id);

        if (! $model) {
            return $this->error('نوع الحساب غير صحيح، أو الحساب غير موجود.', [], 404);
        }

        DB::transaction(function () use ($model, $type, $id, $request) {
            $model->forceFill([
                'status' => 'approved',
                'verified_at' => now(),
                'verified_by' => $request->user()->id,
            ])->save();

            AccountReview::create([
                'account_type' => $type,
                'account_id' => $id,
                'admin_id' => $request->user()->id,
                'decision' => 'approved',
            ]);
        });

        return $this->success(new AdminAccountResource($model->fresh()->load('user')), 'تم اعتماد الحساب بنجاح.');
    }

    public function reject(RejectAccountRequest $request, string $type, int $id): JsonResponse
    {
        $model = $this->resolveAccount($type, $id);

        if (! $model) {
            return $this->error('نوع الحساب غير صحيح، أو الحساب غير موجود.', [], 404);
        }

        DB::transaction(function () use ($model, $type, $id, $request) {
            $model->forceFill([
                'status' => 'rejected',
                'rejection_reason' => $request->validated('reason'),
            ])->save();

            AccountReview::create([
                'account_type' => $type,
                'account_id' => $id,
                'admin_id' => $request->user()->id,
                'decision' => 'rejected',
                'reason' => $request->validated('reason'),
            ]);
        });

        return $this->success(new AdminAccountResource($model->fresh()->load('user')), 'تم رفض الحساب.');
    }

    public function suspend(SuspendAccountRequest $request, string $type, int $id): JsonResponse
    {
        $model = $this->resolveAccount($type, $id);

        if (! $model) {
            return $this->error('نوع الحساب غير صحيح، أو الحساب غير موجود.', [], 404);
        }

        DB::transaction(function () use ($model, $type, $id, $request) {
            $user = $model->user;
            $user->forceFill([
                'status' => 'suspended',
                'suspension_reason' => $request->validated('reason'),
            ])->save();
            $user->tokens()->delete();

            AccountReview::create([
                'account_type' => $type,
                'account_id' => $id,
                'admin_id' => $request->user()->id,
                'decision' => 'suspended',
                'reason' => $request->validated('reason'),
            ]);
        });

        return $this->success(new AdminAccountResource($model->fresh()->load('user')), 'تم إيقاف الحساب.');
    }

    public function activate(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->resolveAccount($type, $id);

        if (! $model) {
            return $this->error('نوع الحساب غير صحيح، أو الحساب غير موجود.', [], 404);
        }

        DB::transaction(function () use ($model, $type, $id, $request) {
            $model->user->forceFill([
                'status' => 'active',
                'suspension_reason' => null,
            ])->save();

            AccountReview::create([
                'account_type' => $type,
                'account_id' => $id,
                'admin_id' => $request->user()->id,
                'decision' => 'activated',
            ]);
        });

        return $this->success(new AdminAccountResource($model->fresh()->load('user')), 'تم تفعيل الحساب من جديد.');
    }

    private function resolveAccount(string $type, int $id): ?Model
    {
        if (! isset(self::TYPE_MODELS[$type])) {
            return null;
        }

        return self::TYPE_MODELS[$type]::find($id);
    }
}
