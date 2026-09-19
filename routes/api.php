<?php

use App\Http\Controllers\ReferenceDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/governorates', [ReferenceDataController::class, 'governorates']);
Route::get('/governorates/{governorate}/areas', [ReferenceDataController::class, 'areas']);
Route::get('/services', [ReferenceDataController::class, 'services']);
Route::get('/activity-types', [ReferenceDataController::class, 'activityTypes']);
