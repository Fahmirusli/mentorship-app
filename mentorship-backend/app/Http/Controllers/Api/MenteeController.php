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

        // 1. Active Mentorships: mentorships where status = 'active'
        $activeMentorships = Mentorship::with(['mentor'])
            ->where('mentee_id', $user->id)
            ->where('status', 'active')
            ->get();

        $mentorships = $activeMentorships->count();

        // 2. Hours Mentored: total completed appointment minutes for this mentee
        $totalCompletedMinutes = Mentorship::where('mentorships.mentee_id', $user->id)
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->where('appointments.status', 'completed')
            ->sum('appointments.duration_minutes');
        $hours = (int) round($totalCompletedMinutes / 60);

        // 3. Learning Progress: from course enrollments
        $courseEnrollments = \App\Models\CourseEnrollment::with('course')
            ->where('mentee_id', $user->id)
            ->get();

        $formattedProgress = $courseEnrollments->map(function ($enrollment) {
            return [
                'name' => $enrollment->course ? $enrollment->course->title : 'Course',
                'progress' => $enrollment->progress_percent ?? 0,
            ];
        })->values()->all();

        // 4. Skills Learning: number of skills the mentee wants to learn (from profile)
        $skillsCount = 0;
        if ($user->menteeProfile) {
            $skillsToLearn = $user->menteeProfile->skills_to_learn ?? [];
            $currentSkills = $user->menteeProfile->current_skills ?? [];
            $skillsCount = count(array_unique(array_merge(
                is_array($skillsToLearn) ? $skillsToLearn : [],
                is_array($currentSkills) ? $currentSkills : []
            )));
        }
        // Fallback: check user->skills if no mentee profile skills
        if ($skillsCount === 0 && !empty($user->skills)) {
            $skillsCount = count(is_array($user->skills) ? $user->skills : []);
        }

        // 5. Job Matches (count of jobs where at least one skill intersects)
        $userSkills = [];
        if ($user->menteeProfile) {
            $userSkills = array_merge(
                is_array($user->menteeProfile->current_skills) ? $user->menteeProfile->current_skills : [],
                is_array($user->menteeProfile->skills_to_learn) ? $user->menteeProfile->skills_to_learn : []
            );
        }
        if (empty($userSkills) && !empty($user->skills)) {
            $userSkills = is_array($user->skills) ? $user->skills : [];
        }

        $jobMatches = 0;
        if (!empty($userSkills)) {
            $jobMatches = \App\Models\Job::where('is_active', true)
                ->get()
                ->filter(function ($job) use ($userSkills) {
                    $required = $job->required_skills ?? [];
                    if (is_string($required)) {
                        $required = json_decode($required, true) ?? [];
                    }
                    if (!is_array($required)) {
                        $required = [];
                    }
                    if (empty($required)) return false;

                    $safeUserSkills = is_array($userSkills) ? $userSkills : [];

                    $matches = array_intersect(
                        array_map('strtolower', $safeUserSkills),
                        array_map('strtolower', $required)
                    );
                    return count($matches) > 0;
                })
                ->count();
        }

        // 6. User Badges
        $badges = $user->badges()->select('badges.id', 'badges.name', 'badges.description', 'badges.icon_url')->get();

        return response()->json([
            'mentorships' => $mentorships,
            'hours'       => $hours,
            'skills'      => $skillsCount,
            'jobs'        => $jobMatches,
            'learning_progress' => $formattedProgress,
            'badges'      => $badges,
        ]);
    }

    public function resources(Request $request)
    {
        $user = $request->user();
        
        $mentorIds = $user->menteeMentorships()
            ->where('status', 'active')
            ->pluck('mentor_id');
            
        $resources = \App\Models\Resource::whereIn('mentor_id', $mentorIds)
            ->with('mentor:id,name')
            ->latest()
            ->get();
            
        return response()->json($resources);
    }
}
