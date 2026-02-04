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

// Public API endpoints (no auth required)
Route::get('/mentors', [App\Http\Controllers\Api\MentorController::class, 'index']);
Route::get('/mentors/{id}', [App\Http\Controllers\Api\MentorController::class, 'show']);
Route::get('/schedules/mentor/{mentorId}', [App\Http\Controllers\Api\ScheduleController::class, 'getMentorSchedule']);
Route::get('/jobs', [App\Http\Controllers\Api\JobController::class, 'index']);

// Telegram Webhook (public, no auth required)
Route::post('/telegram/webhook', [App\Http\Controllers\Api\TelegramWebhookController::class, 'webhook']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['mentorProfile', 'menteeProfile']);
    });
    
    // File uploads
    Route::post('/upload/profile-image', [App\Http\Controllers\Api\FileUploadController::class, 'uploadProfileImage']);
    Route::post('/upload/resume', [App\Http\Controllers\Api\FileUploadController::class, 'uploadResume']);
    Route::put('/user/skills', [App\Http\Controllers\Api\FileUploadController::class, 'updateSkills']);
    
    // Favorites
    Route::get('/favorites', [App\Http\Controllers\Api\FavoriteController::class, 'index']);
    Route::post('/favorites/toggle', [App\Http\Controllers\Api\FavoriteController::class, 'toggle']);
    
    // Job recommendations
    Route::get('/jobs/recommended', [App\Http\Controllers\Api\FileUploadController::class, 'getRecommendedJobs']);
    Route::get('/jobs/recommendations', [App\Http\Controllers\Api\JobController::class, 'recommendations']);
    Route::post('/jobs/scrape', [App\Http\Controllers\Api\JobController::class, 'triggerScrape']);
    Route::post('/jobs', [App\Http\Controllers\Api\JobController::class, 'store']);
    Route::put('/jobs/{id}', [App\Http\Controllers\Api\JobController::class, 'update']);
    Route::delete('/jobs/{id}', [App\Http\Controllers\Api\JobController::class, 'destroy']);
    
    // Mentee & Mentor stats
    Route::get('/mentee/stats', [App\Http\Controllers\Api\MenteeController::class, 'stats']);
    Route::get('/mentor/stats', [App\Http\Controllers\Api\MentorController::class, 'stats']);
    
    // Mentor management (protected operations)
    Route::post('/mentors', [App\Http\Controllers\Api\MentorController::class, 'store']);
    Route::put('/mentors/{id}', [App\Http\Controllers\Api\MentorController::class, 'update']);
    Route::delete('/mentors/{id}', [App\Http\Controllers\Api\MentorController::class, 'destroy']);
    
    // Resources and Management
    Route::apiResource('appointments', App\Http\Controllers\Api\AppointmentController::class);
    Route::patch('/appointments/{id}/reschedule', [App\Http\Controllers\Api\AppointmentController::class, 'reschedule']);
    Route::apiResource('resources', App\Http\Controllers\Api\ResourceController::class);
    Route::apiResource('mentorships', App\Http\Controllers\Api\MentorshipController::class);
    
    // Schedules (protected operations)
    Route::post('/schedules', [App\Http\Controllers\Api\ScheduleController::class, 'store']);
    Route::put('/schedules/{id}', [App\Http\Controllers\Api\ScheduleController::class, 'update']);
    Route::delete('/schedules/{id}', [App\Http\Controllers\Api\ScheduleController::class, 'destroy']);
    Route::get('/schedules/my-schedule', [App\Http\Controllers\Api\ScheduleController::class, 'mySchedule']);
    
    // Invitations
    Route::post('/invite-mentee', [App\Http\Controllers\Api\InvitationController::class, 'send']);
    
    // Profile Routes
    Route::put('/user/profile', [App\Http\Controllers\Api\ProfileController::class, 'update']);
    Route::put('/mentors/profile', [App\Http\Controllers\Api\ProfileController::class, 'updateMentor']);
    Route::post('/user/profile-image', [App\Http\Controllers\Api\ProfileController::class, 'uploadImage']);
    Route::post('/profile/complete', [App\Http\Controllers\Api\ProfileController::class, 'completeProfile']);

    // Telegram Routes
    Route::prefix('telegram')->group(function () {
        Route::get('/link-token', [App\Http\Controllers\Api\TelegramController::class, 'generateLinkToken']);
        Route::post('/link', [App\Http\Controllers\Api\TelegramController::class, 'linkAccount']);
        Route::post('/unlink', [App\Http\Controllers\Api\TelegramController::class, 'unlinkAccount']);
        Route::get('/status', [App\Http\Controllers\Api\TelegramController::class, 'checkStatus']);
    });
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Api\AdminController::class, 'dashboard']);
        Route::get('/users', [App\Http\Controllers\Api\AdminController::class, 'getUsers']);
        Route::put('/users/{id}', [App\Http\Controllers\Api\AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
        Route::post('/users/{id}/verify', [App\Http\Controllers\Api\AdminController::class, 'verifyUser']);
        Route::post('/users/{id}/unverify', [App\Http\Controllers\Api\AdminController::class, 'unverifyUser']);
        Route::post('/users/{id}/telegram-test', [App\Http\Controllers\Api\AdminController::class, 'sendTelegramTest']);
        Route::get('/mentorships', [App\Http\Controllers\Api\AdminController::class, 'getMentorships']);
    });
    
    // Payment Initiate (Protected)
    Route::post('/payment/initiate', [App\Http\Controllers\Api\PaymentController::class, 'initiate']);
});

// Payment Callbacks (Public)
Route::post('/payment/callback', [App\Http\Controllers\Api\PaymentController::class, 'callback'])->name('api.payment.callback');
Route::get('/payment/return', [App\Http\Controllers\Api\PaymentController::class, 'returnPage'])->name('api.payment.return');
