<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MentorProfile;
use Illuminate\Http\Request;

class MentorController extends Controller
{
    public function index(Request $request){
        $query = User::where('role', 'mentor')
            ->where('is_active', true)
            ->with(['mentorProfile', 'schedules']);

        // Filter by skills (from user's skills column)
        if ($request->has('skills') && !empty($request->skills)) {
            $skills = is_array($request->skills) ? $request->skills : [$request->skills];
            $query->where(function($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhereJsonContains('skills', $skill);
                }
            });
        }

        // Filter by expertise (from mentor profile)
        if ($request->has('expertise')) {
            $query->whereHas('mentorProfile', function ($q) use ($request) {
                $q->whereJsonContains('expertise_areas', $request->expertise);
            });
        }

        // Filter by minimum rating
        if ($request->has('rating')) {
            $minRating = (float) $request->rating;
            $query->whereHas('feedbackReceived', function($q) use ($minRating) {
                // This will filter users who have at least one feedback with rating >= minRating
                $q->where('rating', '>=', $minRating);
            });
        }

        // Filter by price range
        if ($request->has('min_price') || $request->has('max_price')) {
            $query->whereHas('mentorProfile', function ($q) use ($request) {
                if ($request->has('min_price')) {
                    $q->where('hourly_rate', '>=', $request->min_price);
                }
                if ($request->has('max_price')) {
                    $q->where('hourly_rate', '<=', $request->max_price);
                }
            });
        }

        // Filter by availability
        if ($request->has('available')) {
            $query->whereHas('mentorProfile', function ($q) {
                $q->where('is_available', true);
            });
        }

        // Search by name, bio, or skills
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('bio', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere(function($q2) use ($search) {
                      // Search in JSON skills array
                      $q2->whereRaw('JSON_SEARCH(skills, "one", ?) IS NOT NULL', ["%{$search}%"]);
                  })
                  ->orWhereHas('mentorProfile', function ($q2) use ($search) {
                      $q2->where('expertise_areas', 'like', "%{$search}%")
                         ->orWhere('industry', 'like', "%{$search}%")
                         ->orWhere('job_title', 'like', "%{$search}%")
                         ->orWhere('company', 'like', "%{$search}%");
                  });
            });
        }

        // Sort by rating, experience, or price
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'rating':
                    // This requires a complex query, simplified for now
                    $query->withAvg('feedbackReceived as avg_rating', 'rating')
                          ->orderByDesc('avg_rating');
                    break;
                case 'experience':
                    $query->whereHas('mentorProfile')->with(['mentorProfile' => function($q) {
                        $q->orderByDesc('years_of_experience');
                    }]);
                    break;
                case 'price_low':
                    $query->whereHas('mentorProfile')->with(['mentorProfile' => function($q) {
                        $q->orderBy('hourly_rate', 'asc');
                    }]);
                    break;
                case 'price_high':
                    $query->whereHas('mentorProfile')->with(['mentorProfile' => function($q) {
                        $q->orderByDesc('hourly_rate');
                    }]);
                    break;
            }
        }

        // Get paginated results
        $perPage = $request->per_page ?? 12;
        $mentors = $query->paginate($perPage);
        
        // Add rating to each mentor
        $mentors->getCollection()->transform(function ($mentor) {
            $avgRating = $mentor->feedbackReceived()->avg('rating');
            $mentor->rating = $avgRating ? round($avgRating, 2) : null;
            $mentor->total_reviews = $mentor->feedbackReceived()->count();
            return $mentor;
        });

        return response()->json($mentors);
    }

    public function show($id){
        $mentor = User::where('role', 'mentor')
            ->with(['mentorProfile', 'feedbackReceived.fromUser'])
            ->findOrFail($id);

        // Calculate average rating
        $avgRating = $mentor->feedbackReceived()->avg('rating');
        $mentor->rating = $avgRating ? round($avgRating, 2) : null;
        $mentor->total_reviews = $mentor->feedbackReceived()->count();

        // Get available schedules for next 14 days
        $availableSchedules = \App\Models\Schedule::where('mentor_id', $id)
            ->where('is_available', true)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where('date', '<=', now()->addDays(14)->format('Y-m-d'))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(function($schedule) {
                return \Carbon\Carbon::parse($schedule->date)->format('Y-m-d');
            });

        $mentor->available_schedules = $availableSchedules;
        $mentor->total_available_slots = \App\Models\Schedule::where('mentor_id', $id)
            ->where('is_available', true)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->count();

        // Calculate stats
        $mentor->total_mentees = $mentor->mentorships()->distinct('mentee_id')->count();
        $mentor->completed_sessions = $mentor->mentorships()
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->where('appointments.status', 'completed')
            ->count();

        return response()->json($mentor);
    }

    public function createProfile(Request $request){
        if ($request->user()->role !== 'mentor') {
            return response()->json([
                'message' => 'Only mentors can create mentor profiles',
            ], 403);
        }

        $validated = $request->validate([
            'expertise_areas' => 'required|array',
            'industry' => 'required|string|max:255',
            'job_title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'years_of_experience' => 'required|integer|min:0',
            'mentorship_approach' => 'nullable|string',
        ]);

        $profile = MentorProfile::create([
            'user_id' => $request->user()->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Mentor profile created successfully',
            'profile' => $profile,
        ], 201);
    }

    public function updateProfile(Request $request){
        if ($request->user()->role !== 'mentor') {
            return response()->json([
                'message' => 'Only mentors can update mentor profiles',
            ], 403);
        }

        $validated = $request->validate([
            'expertise_areas' => 'sometimes|array',
            'industry' => 'sometimes|string|max:255',
            'job_title' => 'sometimes|string|max:255',
            'company' => 'sometimes|string|max:255',
            'years_of_experience' => 'sometimes|integer|min:0',
            'mentorship_approach' => 'sometimes|string',
            'is_available' => 'sometimes|boolean',
        ]);

        $profile = $request->user()->mentorProfile;
        
        if (!$profile) {
            return response()->json([
                'message' => 'Mentor profile not found',
            ], 404);
        }

        $profile->update($validated);

        return response()->json([
            'message' => 'Mentor profile updated successfully',
            'profile' => $profile,
        ]);
    }

    public function stats(Request $request){
        $user = $request->user();

        // 1. Total Mentees (Unique mentees from mentorships)
        $totalMentees = $user->mentorships()->distinct('mentee_id')->count('mentee_id');

        // 2. Hours Provided (Completed sessions)
        // Assuming each session is 1 hour
        $hoursProvided = $user->mentorships()
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->where('appointments.status', 'completed')
            ->count();

        // 3. Earnings
        // Calculate based on completed sessions * fee stored in appointment
        $earnings = $user->mentorships()
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->where('appointments.status', 'completed')
            ->sum('appointments.fee');
        
        // Fallback for old appointments without fee (optional, or just treat as 0 or calculate legacy way)
        // For now, let's assume all new appointments have fee. 
        // If we want to support legacy, we could do IFNULL(fee, 50).
        // But simpler:
        if ($earnings == 0 && $hoursProvided > 0) {
             $hourlyRate = $user->mentorProfile->hourly_rate ?? 50;
             $earnings = $hoursProvided * $hourlyRate;
        }

        // 4. Rating
        // Average of rating from feedbackReceived
        $rating = $user->feedbackReceived()->avg('rating') ?? 0;
        $rating = round($rating, 1);

        // 5. Upcoming Sessions (For the upcoming session list, but not for the top card as requested to remove)
        $upcomingSessions = $user->mentorships()
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->where('appointments.status', 'upcoming')
            ->count();

        return response()->json([
            'totalMentees' => $totalMentees,
            'hoursProvided' => $hoursProvided,
            'earnings' => $earnings,
            'rating' => $rating,
            'upcomingSessions' => $upcomingSessions
        ]);
    }

    public function getNearby(Request $request) {
        // This fetches mentors from your 'Uplift' users table
        $mentors = User::where('role', 'mentor')->get(); 
        return response()->json($mentors);
    }

    /**
     * Get all unique skills available across all mentors.
     * Used by the skill selection screen to show real mentor skills.
     */
    public function getAllSkills()
    {
        $mentors = User::where('role', 'mentor')
            ->where('is_active', true)
            ->whereNotNull('skills')
            ->get();

        $allSkills = [];
        foreach ($mentors as $mentor) {
            $skills = $mentor->skills;
            if (is_string($skills)) {
                $skills = json_decode($skills, true) ?? [];
            }
            if (is_array($skills)) {
                $allSkills = array_merge($allSkills, $skills);
            }
        }

        // Unique, sorted skills
        $uniqueSkills = array_values(array_unique($allSkills));
        sort($uniqueSkills);

        return response()->json([
            'skills' => $uniqueSkills,
            'count' => count($uniqueSkills),
        ]);
    }
}