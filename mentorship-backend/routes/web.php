<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Simple login routes (without full Laravel UI)
Route::get('/', function () {
    return redirect(env('FRONTEND_URL', 'https://uplifts.dev'));
});

Route::get('/login', function () {
    // Always show login form, even if already authenticated
    return view('login');
})->name('login');

Route::post('/login', function (Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials, $request->filled('remember'))) {
        $request->session()->regenerate();

        if (auth()->user()->role === 'admin') {
            return redirect()->intended('/admin/dashboard');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return back()->withErrors([
            'email' => 'This login is for admins only.'
        ])->onlyInput('email');
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
});

Route::post('/logout', function (Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

Route::get('/home', [HomeController::class, 'index'])->name('home');

// Admin routes - require auth and admin role
Route::prefix('admin')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
    
    Route::get('/mentees', [AdminController::class, 'mentees'])->name('admin.mentees');
    Route::get('/mentors', [AdminController::class, 'mentors'])->name('admin.mentors');
    Route::get('/mentorships', [AdminController::class, 'mentorships'])->name('admin.mentorships');
    Route::post('/mentorships', [AdminController::class, 'storeMentorship'])->name('admin.mentorships.store');
    Route::put('/mentorships/{id}', [AdminController::class, 'updateMentorship'])->name('admin.mentorships.update');
    Route::delete('/mentorships/{id}', [AdminController::class, 'deleteMentorship'])->name('admin.mentorships.delete');

    Route::get('/feedbacks', [AdminController::class, 'feedbacks'])->name('admin.feedbacks');
    Route::delete('/feedbacks/{id}', [AdminController::class, 'deleteFeedback'])->name('admin.feedbacks.delete');
    
    Route::get('/jobs', [AdminController::class, 'jobs'])->name('admin.jobs');
    Route::post('/jobs', [AdminController::class, 'storeJob'])->name('admin.jobs.store');
    Route::put('/jobs/{id}', [AdminController::class, 'updateJob'])->name('admin.jobs.update');
    Route::delete('/jobs/{id}', [AdminController::class, 'deleteJob'])->name('admin.jobs.delete');
    Route::post('/jobs/schedule', [AdminController::class, 'updateScrapeSchedule'])->name('admin.jobs.schedule');
    
    Route::get('/revenue', [AdminController::class, 'revenue'])->name('admin.revenue');
    Route::post('/jobs/scrape', [AdminController::class, 'scrapeJobs'])->name('admin.jobs.scrape');
    Route::post('/jobs/{id}/toggle', [AdminController::class, 'toggleVisibility'])->name('admin.jobs.toggle');
});
