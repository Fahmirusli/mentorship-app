<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| These routes are for backward compatibility only. 
| The main frontend is served by Next.js
*/

// Redirect root to frontend or API status
Route::get('/', function () {
    return response()->json(['status' => 'API running', 'version' => '1.0']);
});

// Health check endpoint
Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});

