<?php

use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\CraftsmanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferenceDataController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/governorates', [ReferenceDataController::class, 'governorates']);
Route::get('/governorates/{governorate}/areas', [ReferenceDataController::class, 'areas']);
Route::get('/services', [ReferenceDataController::class, 'services']);
Route::get('/activity-types', [ReferenceDataController::class, 'activityTypes']);

Route::get('/craftsmen', [CraftsmanController::class, 'index']);
Route::get('/craftsmen/featured', [CraftsmanController::class, 'featured']);

Route::post('/contact', [ContactMessageController::class, 'store']);

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

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::post('/service-requests', [ServiceRequestController::class, 'store']);
    Route::get('/service-requests', [ServiceRequestController::class, 'index']);
    Route::post('/service-requests/{serviceRequest}/accept', [ServiceRequestController::class, 'accept']);
    Route::post('/service-requests/{serviceRequest}/reject', [ServiceRequestController::class, 'reject']);
    Route::post('/service-requests/{serviceRequest}/complete', [ServiceRequestController::class, 'complete']);
    Route::post('/service-requests/{serviceRequest}/review', [ServiceRequestController::class, 'review']);
});

Route::middleware(['auth:sanctum', 'verified.email', 'active.account', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/accounts/pending', [AdminAccountController::class, 'pending']);
        Route::get('/accounts/{type}/{id}', [AdminAccountController::class, 'show']);
        Route::post('/accounts/{type}/{id}/approve', [AdminAccountController::class, 'approve']);
        Route::post('/accounts/{type}/{id}/reject', [AdminAccountController::class, 'reject']);
        Route::post('/accounts/{type}/{id}/suspend', [AdminAccountController::class, 'suspend']);
        Route::post('/accounts/{type}/{id}/activate', [AdminAccountController::class, 'activate']);
    });
