<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return $next($request);
        }

        // Check if profile is complete
        $isComplete = $this->isProfileComplete($user);

        if (!$isComplete && !$request->is('api/profile/complete') && !$request->is('api/logout')) {
            return response()->json([
                'message' => 'Please complete your profile before continuing',
                'profile_incomplete' => true,
                'redirect' => '/profile/complete'
            ], 403);
        }

        return $next($request);
    }

    private function isProfileComplete($user): bool
    {
        // Check required fields based on role
        if ($user->role === 'mentor') {
            $skills = is_array($user->skills) ? $user->skills : [];
            return !empty($user->name) 
                && !empty($user->phone) 
                && !empty($user->address)
                && !empty($user->bio) 
                && count($skills) > 0;
        } elseif ($user->role === 'mentee') {
            $skills = is_array($user->skills) ? $user->skills : [];
            return !empty($user->name) 
                && !empty($user->phone) 
                && !empty($user->address)
                && !empty($user->bio)
                && count($skills) > 0;
        }

        return true; // Admin or other roles don't need profile completion
    }
}
