<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Api\MentorController;
use App\Http\Controllers\Api\FeedbackController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification', [AuthController::class, 'resendVerification']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-code', [AuthController::class, 'verifyResetCode']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// OAuth routes
Route::get('/auth/google', [SocialAuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [SocialAuthController::class, 'handleGoogleCallback']);

Route::get('/auth/github', [SocialAuthController::class, 'redirectToGithub']);
Route::get('/auth/github/callback', [SocialAuthController::class, 'handleGithubCallback']);

// Public API endpoints (no auth required)
Route::get('/mentors', [App\Http\Controllers\Api\MentorController::class, 'index']);
Route::get('/mentors/nearby', [App\Http\Controllers\Api\MentorController::class, 'getNearby']);
Route::get('/mentors/all-skills', [App\Http\Controllers\Api\MentorController::class, 'getAllSkills']);
Route::get('/mentors/{id}', [App\Http\Controllers\Api\MentorController::class, 'show']);
Route::get('/schedules/mentor/{mentorId}', [App\Http\Controllers\Api\ScheduleController::class, 'getMentorSchedule']);
Route::get('/jobs', [App\Http\Controllers\Api\JobController::class, 'index']);
Route::get('/jobs/{id}', [App\Http\Controllers\Api\JobController::class, 'show'])->whereNumber('id');



// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        $user = $request->user()->load(['mentorProfile', 'menteeProfile']);
        return array_merge($user->toArray(), [
            'profile_complete' => $user->isProfileComplete(),
            'profile_incomplete' => !$user->isProfileComplete(),
        ]);
    });

    Route::get('/mentee/dashboard', [\App\Http\Controllers\Api\MenteeDashboardController::class, 'getDashboardData']);
    
    // File uploads
    Route::post('/upload/profile-image', [App\Http\Controllers\Api\FileUploadController::class, 'uploadProfileImage']);
    Route::post('/upload/resume', [App\Http\Controllers\Api\FileUploadController::class, 'uploadResume']);
    Route::post('/user/parse-resume', [App\Http\Controllers\Api\ResumeParserController::class, 'parse']);
    Route::put('/user/skills', [App\Http\Controllers\Api\FileUploadController::class, 'updateSkills']);
    
    // Favorites
    Route::get('/favorites', [App\Http\Controllers\Api\FavoriteController::class, 'index']);
    Route::post('/favorites/toggle', [App\Http\Controllers\Api\FavoriteController::class, 'toggle']);
    
    // Job recommendations
    Route::get('/jobs/recommended', [App\Http\Controllers\Api\FileUploadController::class, 'getRecommendedJobs']);
    Route::get('/jobs/recommendations', [App\Http\Controllers\Api\JobController::class, 'recommendations']);
    Route::post('/jobs/scrape', [App\Http\Controllers\Api\JobController::class, 'triggerScrape']);
    Route::post('/jobs', [App\Http\Controllers\Api\JobController::class, 'store']);
    Route::put('/jobs/{id}', [App\Http\Controllers\Api\JobController::class, 'update'])->whereNumber('id');
    Route::delete('/jobs/{id}', [App\Http\Controllers\Api\JobController::class, 'destroy'])->whereNumber('id');

    // Feedback
    Route::get('/feedback', [FeedbackController::class, 'index']);
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy'])->whereNumber('id');
    
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
    Route::put('/mentees/profile', [App\Http\Controllers\Api\MenteeController::class, 'updateProfile']);
    Route::post('/user/profile-image', [App\Http\Controllers\Api\ProfileController::class, 'uploadImage']);
    Route::post('/profile/complete', [App\Http\Controllers\Api\ProfileController::class, 'completeProfile']);
    Route::post('/user/location', [App\Http\Controllers\Api\AuthController::class, 'updateLocation']);
    Route::get('/mentors/nearby', [App\Http\Controllers\Api\MentorController::class, 'getNearby']);


    
    // Payment Initiate (Protected)
    Route::post('/payment/initiate', [App\Http\Controllers\Api\PaymentController::class, 'initiate']);

    // Chat / Messaging
    Route::get('/conversations', [App\Http\Controllers\Api\ChatController::class, 'getConversations']);
    Route::get('/messages/{otherUserId}', [App\Http\Controllers\Api\ChatController::class, 'getMessages']);
    Route::post('/messages/send', [App\Http\Controllers\Api\ChatController::class, 'sendMessage']);
    Route::get('/messages/poll/{conversationId}', [App\Http\Controllers\Api\ChatController::class, 'pollMessages']);

    // Notifications
    Route::get('/notifications', [App\Http\Controllers\Api\NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Api\AdminController::class, 'dashboard']);
    Route::get('/users', [App\Http\Controllers\Api\AdminController::class, 'getUsers']);
    Route::put('/users/{id}', [App\Http\Controllers\Api\AdminController::class, 'updateUser']);
    Route::delete('/users/{id}', [App\Http\Controllers\Api\AdminController::class, 'deleteUser']);
    Route::post('/users/{id}/verify', [App\Http\Controllers\Api\AdminController::class, 'verifyUser']);
    Route::post('/users/{id}/unverify', [App\Http\Controllers\Api\AdminController::class, 'unverifyUser']);

    Route::get('/mentorships', [App\Http\Controllers\Api\AdminController::class, 'getMentorships']);
});

// Payment Callbacks (Public)
Route::post('/payment/callback', [App\Http\Controllers\Api\PaymentController::class, 'callback'])->name('api.payment.callback');
Route::get('/payment/return', [App\Http\Controllers\Api\PaymentController::class, 'returnPage'])->name('api.payment.return');
