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

        // Store pending registration data and TAC for 10 minutes
        Cache::put(
            "pending_registration_{$request->email}",
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role
            ],
            now()->addMinutes(10)
        );
        Cache::put("email_verification_{$request->email}", $tac, now()->addMinutes(10));

        $this->sendTacEmail(
            $request->email,
            'Email Verification - Uplift Mentorship',
            $tac
        );

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

        if ((string) $cachedTac !== (string) $request->tac) {
            return response()->json([
                'message' => 'Invalid verification code.'
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $pending = Cache::get("pending_registration_{$request->email}");
            if (!$pending) {
                return response()->json([
                    'message' => 'Registration expired. Please register again.'
                ], 400);
            }

            $user = User::create([
                'name' => $pending['name'],
                'email' => $pending['email'],
                'password' => $pending['password'],
                'role' => $pending['role'],
                'email_verified_at' => now()
            ]);
        } else {
            $user->email_verified_at = now();
            $user->save();
        }

        Cache::forget("email_verification_{$request->email}");
        Cache::forget("pending_registration_{$request->email}");

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully!',
            'token' => $token,
            'user' => $user,
            'profile_complete' => $user->isProfileComplete(),
            'profile_incomplete' => !$user->isProfileComplete()
        ]);
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
            'user' => $user,
            'profile_complete' => $user->isProfileComplete(),
            'profile_incomplete' => !$user->isProfileComplete()
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

        $this->sendTacEmail(
            $request->email,
            'Password Reset - Uplift Mentorship',
            $tac
        );

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

        if ((string) $cachedTac !== (string) $request->tac) {
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

        if (!$cachedTac || (string) $cachedTac !== (string) $request->tac) {
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

    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $pending = Cache::get("pending_registration_{$request->email}");
            if (!$pending) {
                return response()->json([
                    'message' => 'No pending registration found.'
                ], 404);
            }
        }

        if ($user && $user->email_verified_at) {
            return response()->json([
                'message' => 'Email is already verified'
            ], 400);
        }

        $tac = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put("email_verification_{$request->email}", $tac, now()->addMinutes(10));

        $pending = Cache::get("pending_registration_{$request->email}");
        if ($pending) {
            Cache::put("pending_registration_{$request->email}", $pending, now()->addMinutes(10));
        }

        $this->sendTacEmail(
            $request->email,
            'Email Verification - Uplift Mentorship',
            $tac
        );

        return response()->json([
            'message' => 'Verification code resent'
        ]);
    }

    private function sendTacEmail(string $to, string $subject, string $tac): void
    {
        try {
            Log::info('Attempting to send HTML TAC email to: ' . $to);
            Mail::to($to)->send(new \App\Mail\TacEmail($tac, $subject, ''));
            Log::info('HTML TAC email sent successfully to: ' . $to);
        } catch (\Exception $e) {
            Log::error('Failed to send email to ' . $to . ': ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
