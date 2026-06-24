<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:mentor,mentee',
            'phone' => 'nullable|string|max:20',
        ]);

        // Generate 6-digit TAC
        $tac = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'email_verified_at' => null, // Not verified yet
        ]);

        // Store TAC in cache for 10 minutes
        \Cache::put('email_verification_' . $user->email, $tac, now()->addMinutes(10));

        // Send verification email
        try {
            \Mail::raw(
                "Welcome to MentorCore!\n\n" .
                "Your email verification code (TAC) is: {$tac}\n\n" .
                "This code will expire in 10 minutes.\n\n" .
                "If you didn't register for MentorCore, please ignore this email.",
                function ($message) use ($user) {
                    $message->to($user->email)
                            ->subject('Email Verification - MentorCore');
                }
            );
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: ' . $e->getMessage());
            // Continue anyway - user can request resend
        }



        return response()->json([
            'message' => 'Registration successful. Please check your email for verification code.',
            'email' => $user->email,
        ], 201);
    }

    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'tac' => 'required|string|size:6',
        ]);

        $cachedTac = \Cache::get('email_verification_' . $validated['email']);

        if (!$cachedTac) {
            return response()->json([
                'message' => 'Verification code has expired. Please register again.',
            ], 400);
        }

        if ($cachedTac !== $validated['tac']) {
            return response()->json([
                'message' => 'Invalid verification code.',
            ], 400);
        }

        // Verify the user
        $user = User::where('email', $validated['email'])->first();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }

        $user->update(['email_verified_at' => now()]);
        
        // Clear the TAC from cache
        \Cache::forget('email_verification_' . $validated['email']);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user->load(['mentorProfile', 'menteeProfile']),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load(['mentorProfile', 'menteeProfile']),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'bio' => 'sometimes|string',
            'skills' => 'sometimes|array',
            'interests' => 'sometimes|array',
            'profile_image' => 'sometimes|string',
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Implement password reset logic here
        // For now, return success message
        return response()->json([
            'message' => 'Password reset link sent to your email',
        ]);
    }

    public function updateLocation(Request $request)
    {
        // 1. Validate the coordinates
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // 2. Get the currently logged-in user and update their database row
        $user = $request->user();
        $user->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'message' => 'Location updated successfully in the background.',
            'latitude' => $user->latitude,
            'longitude' => $user->longitude,
        ]);
    }

    public function requestTac(Request $request)
    {
        $user = $request->user();
        
        // Generate 6-digit TAC
        $tac = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Store TAC in cache for 5 minutes
        \Cache::put('tac_password_change_' . $user->id, $tac, now()->addMinutes(5));
        
        // For demonstration, return the TAC in the response. In a real app, send it via email/SMS.
        return response()->json([
            'message' => 'TAC generated successfully. (Check your alert popup or console for the code)',
            'tac_for_testing' => $tac
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'tac' => 'required|string|size:6'
        ]);

        // Validate TAC
        $cachedTac = \Cache::get('tac_password_change_' . $user->id);
        
        if (!$cachedTac) {
            return response()->json(['message' => 'TAC has expired or is invalid. Please request a new one.'], 400);
        }
        
        if ($cachedTac !== $validated['tac']) {
            return response()->json(['message' => 'Invalid TAC.'], 400);
        }

        // Validate current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        // Change password
        $user->password = Hash::make($validated['new_password']);
        $user->save();
        
        // Clear TAC
        \Cache::forget('tac_password_change_' . $user->id);

        return response()->json([
            'message' => 'Password changed successfully!'
        ]);
    }
}