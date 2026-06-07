<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenteeProfile;
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
        
        // 1. Active Mentorships
        $mentorships = $user->menteeMentorships()->where('status', 'active')->count();
        
        // 2. Hours Mentored
        $hours = $user->menteeMentorships()
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->where('appointments.status', 'completed')
            ->count();
            
        // 3. Skills Learning (From Active Mentorships)
        // We get the unique skills from the mentors of active mentorships or the mentorship goals
        $activeMentorships = $user->menteeMentorships()
            ->where('status', 'active')
            ->with(['mentor.mentorProfile'])
            ->get();

        $learningProgress = [];
        $skillsCount = 0;

        foreach ($activeMentorships as $mentorship) {
            // Assume we learn the mentor's top expertise
            $mentorSkills = $mentorship->mentor->mentorProfile->expertise ?? [];
            foreach ($mentorSkills as $skill) {
                // Mock progress calculation based on time elapsed
                // In a real app, this would be tracked explicitly
                $daysActive = now()->diffInDays($mentorship->start_date);
                $progress = min(100, max(10, $daysActive * 2)); // 2% per day

                // Avoid duplicates, keep highest progress
                if (!isset($learningProgress[$skill])) {
                    $learningProgress[$skill] = $progress;
                    $skillsCount++;
                }
            }
        }
        
        // Format for frontend
        $formattedProgress = [];
        foreach ($learningProgress as $skill => $progress) {
             $formattedProgress[] = [
                 'name' => $skill,
                 'progress' => $progress
             ];
        }

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
