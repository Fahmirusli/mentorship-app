<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MentorProfile;
use Illuminate\Http\Request;

class MentorController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'mentor')
            ->where('is_active', true)
            ->with(['mentorProfile']);

        // Filter by expertise
        if ($request->has('expertise')) {
            $query->whereHas('mentorProfile', function ($q) use ($request) {
                $q->whereJsonContains('expertise_areas', $request->expertise);
            });
        }

        // Filter by availability
        if ($request->has('available')) {
            $query->whereHas('mentorProfile', function ($q) {
                $q->where('is_available', true);
            });
        }

        // Search by name or industry
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('mentorProfile', function ($q2) use ($search) {
                      $q2->where('industry', 'like', "%{$search}%")
                         ->orWhere('job_title', 'like', "%{$search}%");
                  });
            });
        }

        $mentors = $query->paginate(12);

        return response()->json($mentors);
    }

    public function show($id)
    {
        $mentor = User::where('role', 'mentor')
            ->with(['mentorProfile', 'schedules', 'feedbackReceived.fromUser'])
            ->findOrFail($id);

        return response()->json($mentor);
    }

    public function createProfile(Request $request)
    {
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

    public function updateProfile(Request $request)
    {
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
    public function stats(Request $request)
    {
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
}