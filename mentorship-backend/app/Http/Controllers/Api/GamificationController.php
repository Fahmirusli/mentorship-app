<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Badge;

class GamificationController extends Controller
{
    public function getGamificationData(Request $request)
    {
        $user = $request->user();
        
        $userBadges = $user->badges()->get()->map(function ($badge) {
            return [
                'id' => $badge->id,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon_url' => $badge->icon_url,
                'awarded_at' => $badge->pivot->created_at->toISOString(),
            ];
        });

        $allBadges = Badge::orderBy('required_points', 'asc')->get()->map(function ($badge) use ($userBadges) {
            $isEarned = $userBadges->contains('id', $badge->id);
            return [
                'id' => $badge->id,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon_url' => $badge->icon_url,
                'required_points' => $badge->required_points,
                'is_earned' => $isEarned,
            ];
        });

        return response()->json([
            'points' => $user->points,
            'earned_badges' => $userBadges,
            'all_badges' => $allBadges,
        ]);
    }
}
