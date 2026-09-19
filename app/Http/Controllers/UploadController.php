<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadFileRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    use ApiResponse;

    public function store(UploadFileRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('uploads', 'public');

        return $this->success([
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ], 'تم رفع الملف بنجاح.', 201);
    }
}
