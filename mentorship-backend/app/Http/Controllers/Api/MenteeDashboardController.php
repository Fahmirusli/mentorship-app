<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Job;
use App\Models\Appointment;
use Carbon\Carbon;

class MenteeDashboardController extends Controller
{
    public function getDashboardData(Request $request)
    {
        $user = $request->user(); // The logged-in mentee

        // 1. Get User & Mentee Profile Skills
        $menteeProfile = $user->menteeProfile;
        
        // Safely check if it's already an array (model cast) or still a JSON string
        $currentSkills = $menteeProfile->current_skills ?? [];
        if (is_string($currentSkills)) {
            $currentSkills = json_decode($currentSkills, true) ?? [];
        }

        $skillsToLearn = $menteeProfile->skills_to_learn ?? ['React', 'Laravel', 'Flutter'];
        if (is_string($skillsToLearn)) {
            $skillsToLearn = json_decode($skillsToLearn, true) ?? ['React', 'Laravel', 'Flutter'];
        }// Fallback if empty

        // 2. Get Today's Schedule (Through mentorship relation)
        $userMentorshipIds = \App\Models\Mentorship::where('mentee_id', $user->id)
            ->orWhere('mentor_id', $user->id)
            ->pluck('id');

        $todaySchedule = Appointment::with(['mentorship.mentor', 'mentorship.mentee'])
            ->whereIn('mentorship_id', $userMentorshipIds)
            ->whereDate('scheduled_at', Carbon::today())
            ->whereIn('status', ['scheduled', 'pending_payment'])
            ->orderBy('scheduled_at', 'asc')
            ->get()
            ->map(function ($apt) use ($user) {
                $mentorship = $apt->mentorship;
                $otherUser = ($mentorship->mentor_id == $user->id) ? $mentorship->mentee : $mentorship->mentor;
                return [
                    'id' => $apt->id,
                    'time' => Carbon::parse($apt->scheduled_at)->format('h:i A'),
                    'mentor_name' => $mentorship->mentor ? $mentorship->mentor->name : 'Unknown',
                    'mentee_name' => $mentorship->mentee ? $mentorship->mentee->name : 'Unknown',
                    'other_user_name' => $otherUser ? $otherUser->name : 'Unknown',
                    'status' => $apt->status,
                    'meeting_link' => $apt->meeting_link,
                    'duration_minutes' => $apt->duration_minutes,
                ];
            });

        // 3. AI-Powered Mentorship Matching Algorithm (Compatibility Scoring)
        $allMentors = User::where('role', 'mentor')
            ->where('is_active', true)
            ->with('mentorProfile')
            ->get();

        $scoredMentors = [];
        
        // Normalize mentee's desired skills
        $menteeSkillsNorm = array_map(fn($s) => trim(strtolower($s)), $skillsToLearn);

        foreach ($allMentors as $mentor) {
            $mentorSkills = $mentor->skills ?? [];
            if (is_string($mentorSkills)) {
                $mentorSkills = json_decode($mentorSkills, true) ?? [];
            }
            // Fallback to profile
            if (empty($mentorSkills) && $mentor->mentorProfile) {
                $profileSkills = $mentor->mentorProfile->expertise ?? [];
                if (is_string($profileSkills)) {
                    $profileSkills = json_decode($profileSkills, true) ?? [];
                }
                $mentorSkills = $profileSkills;
            }

            $mentorSkillsNorm = array_map(fn($s) => trim(strtolower($s)), $mentorSkills);
            
            // Calculate Compatibility Score using Content-Based Skill Matching
            $matches = 0;
            foreach ($menteeSkillsNorm as $mSkill) {
                if (empty($mSkill)) continue;
                foreach ($mentorSkillsNorm as $mentorSkill) {
                    if (empty($mentorSkill)) continue;
                    // Considers exact matches and related skills (partial matches)
                    if ($mentorSkill === $mSkill || str_contains($mentorSkill, $mSkill) || str_contains($mSkill, $mentorSkill)) {
                        $matches++;
                        break;
                    }
                }
            }
            
            $totalSkills = count($menteeSkillsNorm) ?: 1;
            $compatibilityScore = round(($matches / $totalSkills) * 100, 2);

            $profile = $mentor->mentorProfile;
            $scoredMentors[] = [
                'id' => $mentor->id,
                'name' => $mentor->name,
                'job_title' => $profile ? $profile->job_title : 'Expert',
                'company' => $profile ? $profile->company : 'Independent',
                'rating' => $profile ? $profile->rating : 0.00,
                'hourly_rate' => $profile ? $profile->hourly_rate : 50.00,
                'compatibility_score' => $compatibilityScore
            ];
        }

        // Sort by compatibility score descending, then by rating descending
        usort($scoredMentors, function($a, $b) {
            if ($b['compatibility_score'] == $a['compatibility_score']) {
                return $b['rating'] <=> $a['rating'];
            }
            return $b['compatibility_score'] <=> $a['compatibility_score'];
        });

        // Take top 3 best matching mentors
        $recommendedMentors = array_slice($scoredMentors, 0, 3);

        // 4. Get Job Recommendations (From 'jobs' table)
        $jobRecommendations = Job::where('is_active', true)
            ->latest('posted_date')
            ->take(3)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'title' => $job->title,
                    'company' => $job->company,
                    'location' => $job->location,
                    'salary' => $job->salary ?? $job->salary_range,
                ];
            });

        // 5. Package it all into one JSON response
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'skills_to_learn' => $skillsToLearn,
                ],
                'today_schedule' => $todaySchedule,
                'recommended_mentors' => $recommendedMentors,
                'job_recommendations' => $jobRecommendations,
            ]
        ]);
    }
}