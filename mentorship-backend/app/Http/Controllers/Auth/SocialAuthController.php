
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    public function redirectToGoogle()
    {
        if (!env('GOOGLE_CLIENT_ID') || !env('GOOGLE_CLIENT_SECRET')) {
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_not_configured');
        }
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'email_verified_at' => now()
                ]);
            } else {
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(Str::random(24)),
                    'role' => request()->get('role', 'mentee'),
                    'email_verified_at' => now()
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $redirectUrl = "{$frontendUrl}/auth/callback?token={$token}&user=" . urlencode(json_encode($user));
            
            return redirect($redirectUrl);
            
        } catch (\Exception $e) {
            Log::error('Google OAuth error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_failed');
        }
    }

    public function redirectToLinkedIn()
    {
        if (!env('LINKEDIN_CLIENT_ID') || !env('LINKEDIN_CLIENT_SECRET')) {
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_not_configured');
        }
        return Socialite::driver('linkedin')->redirect();
    }

    public function handleLinkedInCallback()
    {
        try {
            $linkedinUser = Socialite::driver('linkedin')->user();
            
            $user = User::where('email', $linkedinUser->email)->first();

            if ($user) {
                $user->update([
                    'linkedin_id' => $linkedinUser->id,
                    'avatar' => $linkedinUser->avatar,
                    'email_verified_at' => now()
                ]);
            } else {
                $user = User::create([
                    'name' => $linkedinUser->name,
                    'email' => $linkedinUser->email,
                    'linkedin_id' => $linkedinUser->id,
                    'avatar' => $linkedinUser->avatar,
                    'password' => Hash::make(Str::random(24)),
                    'role' => request()->get('role', 'mentee'),
                    'email_verified_at' => now()
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $redirectUrl = "{$frontendUrl}/auth/callback?token={$token}&user=" . urlencode(json_encode($user));
            
            return redirect($redirectUrl);
            
        } catch (\Exception $e) {
            Log::error('LinkedIn OAuth error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_failed');
        }
    }

    public function redirectToGithub()
    {
        if (!env('GITHUB_CLIENT_ID') || !env('GITHUB_CLIENT_SECRET')) {
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_not_configured');
        }
        return Socialite::driver('github')->stateless()->redirect();
    }

    public function handleGithubCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();
            
            $user = User::where('email', $githubUser->email)->first();

            if ($user) {
                $user->update([
                    'github_id' => $githubUser->id,
                    'avatar' => $githubUser->avatar,
                    'email_verified_at' => now()
                ]);
            } else {
                $user = User::create([
                    'name' => $githubUser->name ?? $githubUser->nickname, 
                    'email' => $githubUser->email,
                    'github_id' => $githubUser->id,
                    'avatar' => $githubUser->avatar,
                    'password' => Hash::make(Str::random(24)),
                    'role' => request()->get('role', 'mentee'),
                    'email_verified_at' => now()
                ]);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
            $redirectUrl = "{$frontendUrl}/auth/callback?token={$token}&user=" . urlencode(json_encode($user));
            
            return redirect($redirectUrl);
            
        } catch (\Exception $e) {
            Log::error('GitHub OAuth error: ' . $e->getMessage());
            return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/login?error=oauth_failed');
        }
    }
}
