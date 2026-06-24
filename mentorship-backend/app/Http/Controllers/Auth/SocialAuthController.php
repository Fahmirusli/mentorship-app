<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirectToGoogle(Request $request)
    {
        if (!env('GOOGLE_CLIENT_ID') || !env('GOOGLE_CLIENT_SECRET')) {
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_not_configured');
        }
        $state = Str::random(40);
        $redirect = $request->query('redirect');
        $role = $request->query('role');

        if ($redirect || $role) {
            Cache::put("oauth_state_{$state}", [
                'redirect' => $redirect,
                'role' => $role
            ], now()->addMinutes(10));
        }

        return Socialite::driver('google')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $state = $request->get('state');
            $stateData = $state ? Cache::pull("oauth_state_{$state}") : null;
            $role = $stateData['role'] ?? $request->get('role', 'mentee');
            $customRedirect = $stateData['redirect'] ?? null;
            
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Update existing user
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'email_verified_at' => now()
                ]);
            } else {
                // Redirect back to login with error
                return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=not_registered');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect to frontend with token
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $baseUrl = $customRedirect ?: "{$frontendUrl}/auth/callback";
            $redirectUrl = $this->buildRedirectUrl($baseUrl, $token, $user);

            return redirect($redirectUrl);
            
        } catch (\Exception $e) {
            Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_failed');
        }
    }

    public function redirectToGithub(Request $request)
    {
        if (!env('GITHUB_CLIENT_ID') || !env('GITHUB_CLIENT_SECRET')) {
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_not_configured');
        }
        $state = Str::random(40);
        $redirect = $request->query('redirect');
        $role = $request->query('role');

        if ($redirect || $role) {
            Cache::put("oauth_state_{$state}", [
                'redirect' => $redirect,
                'role' => $role
            ], now()->addMinutes(10));
        }

        return Socialite::driver('github')
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    public function handleGithubCallback(Request $request)
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();

            $state = $request->get('state');
            $stateData = $state ? Cache::pull("oauth_state_{$state}") : null;
            $role = $stateData['role'] ?? $request->get('role', 'mentee');
            $customRedirect = $stateData['redirect'] ?? null;
            
            $user = User::where('email', $githubUser->email)->first();

            if ($user) {
                // Update existing user
                $user->update([
                    'github_id' => $githubUser->id,
                    'avatar' => $githubUser->avatar, // Priority to latest social login avatar
                    'email_verified_at' => now()
                ]);
            } else {
                // Redirect back to login with error
                return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=not_registered');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Redirect to frontend with token
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $baseUrl = $customRedirect ?: "{$frontendUrl}/auth/callback";
            $redirectUrl = $this->buildRedirectUrl($baseUrl, $token, $user);

            return redirect($redirectUrl);
            
        } catch (\Exception $e) {
            Log::error('GitHub OAuth error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_failed');
        }
    }

    private function buildRedirectUrl(string $baseUrl, string $token, User $user): string
    {
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $profileComplete = $user->isProfileComplete() ? '1' : '0';

        return $baseUrl
            . $separator
            . 'token=' . urlencode($token)
            . '&user=' . urlencode(json_encode($user))
            . '&profile_complete=' . $profileComplete;
    }
}
