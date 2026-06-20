<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenteeProfile;
use App\Models\Mentorship;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MenteeController extends Controller
{
    public function createProfile(Request $request)
    {
        if ($request->user()->role !== 'mentee') {
            return response()->json([
                'message' => 'Only mentees can create mentee profiles',
            ], 403);
        }

        $validated = $request->validate([
            'current_skills' => 'required|array',
            'skills_to_learn' => 'required|array',
            'career_goals' => 'required|string',
            'education_level' => 'required|string|max:255',
            'field_of_study' => 'required|string|max:255',
        ]);

        $profile = MenteeProfile::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Mentee profile created successfully',
            'profile' => $profile,
        ], 201);
    }

    public function updateProfile(Request $request)
    {
        if ($request->user()->role !== 'mentee') {
            return response()->json([
                'message' => 'Only mentees can update mentee profiles',
            ], 403);
        }

        $validated = $request->validate([
            'current_skills' => 'sometimes|array',
            'skills_to_learn' => 'sometimes|array',
            'career_goals' => 'sometimes|string',
            'education_level' => 'sometimes|string|max:255',
            'field_of_study' => 'sometimes|string|max:255',
        ]);

        $profile = $request->user()->menteeProfile;
        
        if (!$profile) {
            $profile = MenteeProfile::create([
                'user_id' => $request->user()->id,
            ]);
        }

        $profile->update($validated);

        return response()->json([
            'message' => 'Mentee profile updated successfully',
            'profile' => $profile,
        ]);
    }

    public function stats(Request $request)
    {
        $user = $request->user();

        $upcomingStatuses = ['scheduled', 'pending_payment', 'rescheduled'];

        $activeMentorshipIds = Mentorship::query()
            ->where('mentee_id', $user->id)
            ->whereHas('appointments', function ($query) use ($upcomingStatuses) {
                $query->whereIn('status', $upcomingStatuses)
                    ->where('scheduled_at', '>=', now());
            })
            ->pluck('id');

        // 1. Active Mentorships are mentorships with at least one upcoming appointment.
        $mentorships = $activeMentorshipIds->count();

        // 2. Hours Mentored is completed session duration for this mentee.
        $totalCompletedMinutes = $user->menteeMentorships()
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->where('appointments.status', 'completed')
            ->sum('appointments.duration_minutes');
        $hours = (int) round($totalCompletedMinutes / 60);

        // 3. Learning progress is derived from completed vs total sessions per active mentorship.
        $activeMentorships = Mentorship::with(['mentor'])
            ->whereIn('id', $activeMentorshipIds)
            ->get();

        $formattedProgress = $activeMentorships->map(function (Mentorship $mentorship) {
            $completedCount = $mentorship->appointments()
                ->where('status', 'completed')
                ->count();

            $totalCount = $mentorship->appointments()->count();
            $progress = $totalCount > 0
                ? (int) round(($completedCount / $totalCount) * 100)
                : (int) min(100, max(5, Carbon::parse($mentorship->created_at)->diffInDays(now())));

            return [
                'name' => $mentorship->goals
                    ? 'Goal: ' . \Illuminate\Support\Str::limit((string) $mentorship->goals, 30)
                    : 'Mentor: ' . ($mentorship->mentor?->name ?? 'Mentor'),
                'progress' => min(100, max(0, $progress)),
            ];
        })->values()->all();

        $skillsCount = count($formattedProgress);

        // 4. Job Matches (Real matching logic)
        $userSkills = [];
        if ($user->menteeProfile) {
             $userSkills = array_merge(
                 $user->menteeProfile->current_skills ?? [],
                 $user->menteeProfile->skills_to_learn ?? []
             );
        }

        $jobMatches = 0;
        if (!empty($userSkills)) {
            // Count jobs where at least one skill matches
            // This is a simplified "partial match" count
            $jobMatches = \App\Models\Job::where('is_active', true)
                ->get()
                ->filter(function ($job) use ($userSkills) {
                    $required = $job->required_skills ?? [];
                    if (empty($required)) return false;
                    
                    $matches = array_intersect(
                        array_map('strtolower', $userSkills),
                        array_map('strtolower', $required)
                    );
                    
                    return count($matches) > 0;
                })
                ->count();
        }

        return response()->json([
            'mentorships' => $mentorships,
            'hours' => $hours,
            'skills' => $skillsCount,
            'jobs' => $jobMatches,
            'learning_progress' => $formattedProgress 
        ]);
    }
}
