<?php

namespace App\Services;

use App\Models\User;
use App\Models\Badge;
use App\Models\NotificationLog;

class GamificationService
{
    /**
     * Award points to a user and check for new badges.
     */
    public function awardPoints(User $user, int $points, string $reason = '')
    {
        $user->points += $points;
        $user->save();

        if ($reason) {
            NotificationLog::notify(
                $user->id,
                'system',
                'Points Earned!',
                "You earned {$points} points for {$reason}.",
                ['points' => $points]
            );
        }

        $this->checkForNewBadges($user);
    }

    /**
     * Check if the user qualifies for any new badges based on their points.
     */
    public function checkForNewBadges(User $user)
    {
        $earnedBadgeIds = $user->badges()->pluck('badges.id')->toArray();

        // Find badges the user qualifies for but hasn't earned yet
        $newBadges = Badge::where('required_points', '<=', $user->points)
            ->whereNotIn('id', $earnedBadgeIds)
            ->get();

        foreach ($newBadges as $badge) {
            $user->badges()->attach($badge->id);

            NotificationLog::notify(
                $user->id,
                'system',
                'New Badge Unlocked!',
                "Congratulations! You unlocked the '{$badge->name}' badge.",
                ['badge_id' => $badge->id, 'badge_icon' => $badge->icon_url]
            );
        }
    }
}
