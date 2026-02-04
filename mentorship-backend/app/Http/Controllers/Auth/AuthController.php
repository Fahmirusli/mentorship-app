<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:mentee,mentor'
        ]);

        // Generate 6-digit TAC
        $tac = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store TAC in cache for 10 minutes
        Cache::put("email_verification_{$request->email}", $tac, now()->addMinutes(10));
        
        // Create user but mark as unverified
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified_at' => null
        ]);

        // Send TAC via email
        try {
            Mail::raw("Your verification code (TAC) is: {$tac}\n\nThis code will expire in 10 minutes.", function($message) use ($request) {
                $message->to($request->email)
                        ->subject('Email Verification - Uplift Mentorship');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Registration successful! Please check your email for verification code.',
            'email' => $request->email
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'tac' => 'required|string|size:6'
        ]);

        $cachedTac = Cache::get("email_verification_{$request->email}");

        if (!$cachedTac) {
            return response()->json([
                'message' => 'Verification code expired. Please request a new one.'
            ], 400);
        }

        if ($cachedTac !== $request->tac) {
            return response()->json([
                'message' => 'Invalid verification code.'
            ], 400);
        }

        // Verify user
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->email_verified_at = now();
            $user->save();
            
            Cache::forget("email_verification_{$request->email}");
            
            return response()->json([
                'message' => 'Email verified successfully!'
            ]);
        }

        return response()->json([
            'message' => 'User not found.'
        ], 404);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json([
                'message' => 'Please verify your email first'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Generate 6-digit TAC
        $tac = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store TAC in cache for 10 minutes
        Cache::put("password_reset_{$request->email}", $tac, now()->addMinutes(10));

        // Send TAC via email
        try {
            Mail::raw("Your password reset code (TAC) is: {$tac}\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this, please ignore this email.", function($message) use ($request) {
                $message->to($request->email)
                        ->subject('Password Reset - Uplift Mentorship');
            });
        } catch (\Exception $e) {
            Log::error('Failed to send reset email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Password reset code sent to your email'
        ]);
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'tac' => 'required|string|size:6'
        ]);

        $cachedTac = Cache::get("password_reset_{$request->email}");

        if (!$cachedTac) {
            return response()->json([
                'message' => 'Reset code expired. Please request a new one.'
            ], 400);
        }

        if ($cachedTac !== $request->tac) {
            return response()->json([
                'message' => 'Invalid reset code.'
            ], 400);
        }

        return response()->json([
            'message' => 'Code verified successfully'
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'tac' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $cachedTac = Cache::get("password_reset_{$request->email}");

        if (!$cachedTac || $cachedTac !== $request->tac) {
            return response()->json([
                'message' => 'Invalid or expired reset code'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        Cache::forget("password_reset_{$request->email}");

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
