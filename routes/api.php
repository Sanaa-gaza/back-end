<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\UploadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/governorates', [ReferenceDataController::class, 'governorates']);
Route::get('/governorates/{governorate}/areas', [ReferenceDataController::class, 'areas']);
Route::get('/services', [ReferenceDataController::class, 'services']);
Route::get('/activity-types', [ReferenceDataController::class, 'activityTypes']);

Route::post('/uploads', [UploadController::class, 'store']);

Route::post('/auth/register/customer', [RegisterController::class, 'customer']);
Route::post('/auth/register/craftsman', [RegisterController::class, 'craftsman']);
Route::post('/auth/register/institution', [RegisterController::class, 'institution']);
Route::post('/auth/register/contractor', [RegisterController::class, 'contractor']);

Route::post('/auth/verify-email', [VerificationController::class, 'verifyEmail']);
Route::post('/auth/resend-code', [VerificationController::class, 'resendCode']);
