<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

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

Route::post('/auth/login', [LoginController::class, 'login']);

Route::post('/auth/forgot-password', [VerificationController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [VerificationController::class, 'resetPassword']);

Route::middleware(['auth:sanctum', 'verified.email', 'active.account'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});
