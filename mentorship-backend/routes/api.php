<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// OAuth routes
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);
Route::get('/auth/linkedin', [SocialAuthController::class, 'redirectToLinkedIn']);
Route::get('/auth/linkedin/callback', [SocialAuthController::class, 'handleLinkedInCallback']);
Route::get('/auth/github', [SocialAuthController::class, 'redirectToGithub']);
Route::get('/auth/github/callback', [SocialAuthController::class, 'handleGithubCallback']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Your other API routes here...
    Route::get('/jobs/recommendations', [App\Http\Controllers\Api\JobController::class, 'recommendations']);
    Route::get('/mentee/stats', [App\Http\Controllers\Api\MenteeController::class, 'stats']);
    Route::post('/jobs/scrape', [App\Http\Controllers\Api\JobController::class, 'triggerScrape']);
    Route::apiResource('jobs', App\Http\Controllers\Api\JobController::class);
    Route::get('/mentor/stats', [App\Http\Controllers\Api\MentorController::class, 'stats']);
    Route::apiResource('mentors', App\Http\Controllers\Api\MentorController::class);
    Route::apiResource('appointments', App\Http\Controllers\Api\AppointmentController::class);
    Route::apiResource('resources', App\Http\Controllers\Api\ResourceController::class);
    Route::apiResource('mentorships', App\Http\Controllers\Api\MentorshipController::class);
    Route::apiResource('schedules', App\Http\Controllers\Api\ScheduleController::class);
    Route::get('/schedules/mentor/{mentorId}', [App\Http\Controllers\Api\ScheduleController::class, 'getMentorSchedule']);
    Route::post('/invite-mentee', [App\Http\Controllers\Api\InvitationController::class, 'send']);
    
    // Profile Routes
    Route::put('/user/profile', [App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::put('/mentors/profile', [App\Http\Controllers\Api\ProfileController::class, 'updateMentor']);
    Route::post('/user/profile-image', [App\Http\Controllers\Api\ProfileController::class, 'uploadImage']);

    // Admin Routes
    // Admin Routes
    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\AdminController::class, 'dashboard']);
        Route::get('/users', [App\Http\Controllers\Api\AdminController::class, 'getUsers']);
        Route::put('/users/{id}', [App\Http\Controllers\Api\AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
        Route::get('/mentorships', [App\Http\Controllers\Api\AdminController::class, 'getMentorships']);
    });
    
    // Payment Initiate (Protected)
    Route::post('/payment/initiate', [App\Http\Controllers\Api\PaymentController::class, 'initiate']);
});

// Payment Callbacks (Public)
Route::post('/payment/callback', [App\Http\Controllers\Api\PaymentController::class, 'callback'])->name('api.payment.callback');
Route::get('/payment/return', [App\Http\Controllers\Api\PaymentController::class, 'returnPage'])->name('api.payment.return');