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

        // Filter by skills (from user's skills column OR mentor profile expertise)
        if ($request->has('skills') && !empty($request->skills)) {
            $skills = is_array($request->skills) ? $request->skills : [$request->skills];
            $query->where(function($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhereJsonContains('skills', $skill)
                      ->orWhereHas('mentorProfile', function($mp) use ($skill) {
                          $mp->whereJsonContains('expertise_areas', $skill);
                      });
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
                      // Search in JSON skills array (stored as string)
                      $q2->where('skills', 'like', "%{$search}%");
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
                    $query->leftJoin('mentor_profiles as mp_sort', 'users.id', '=', 'mp_sort.user_id')
                          ->orderByDesc('mp_sort.years_of_experience')
                          ->select('users.*');
                    break;
                case 'price_low':
                    $query->leftJoin('mentor_profiles as mp_sort', 'users.id', '=', 'mp_sort.user_id')
                          ->orderBy('mp_sort.hourly_rate', 'asc')
                          ->select('users.*');
                    break;
                case 'price_high':
                    $query->leftJoin('mentor_profiles as mp_sort', 'users.id', '=', 'mp_sort.user_id')
                          ->orderByDesc('mp_sort.hourly_rate')
                          ->select('users.*');
                    break;
            }
        }

        // Get paginated results
        $perPage = $request->per_page ?? 12;
        $mentors = $query->paginate($perPage);
        
        // Add rating and availability info to each mentor
        $today = now(config('app.timezone'))->toDateString();
        $currentTime = now(config('app.timezone'))->format('H:i:s');

        $mentors->getCollection()->transform(function ($mentor) use ($today, $currentTime) {
            $avgRating = $mentor->feedbackReceived()->avg('rating');
            $mentor->rating = $avgRating ? round($avgRating, 2) : null;
            $mentor->total_reviews = $mentor->feedbackReceived()->count();

            // Count available future slots
            $availableSlots = \App\Models\Schedule::where('mentor_id', $mentor->id)
                ->where('is_available', true)
                ->where(function ($q) use ($today, $currentTime) {
                    $q->where('date', '>', $today)
                      ->orWhere(function ($same) use ($today, $currentTime) {
                          $same->where('date', $today)
                               ->where('start_time', '>', $currentTime);
                      });
                })
                ->orderBy('date')
                ->orderBy('start_time')
                ->get(['date', 'start_time', 'end_time', 'fee']);

            $mentor->available_slots_count = $availableSlots->count();

            // Next available slot
            $next = $availableSlots->first();
            $mentor->next_available_slot = $next
                ? [
                    'date'       => $next->date,
                    'start_time' => $next->start_time,
                    'end_time'   => $next->end_time,
                    'fee'        => $next->fee,
                ]
                : null;

            return $mentor;
        });

        return response()->json($mentors);
    }

    public function show($id){
        $mentor = User::where('role', 'mentor')
            ->with(['mentorProfile', 'feedbackReceived.fromUser'])
            ->findOrFail($id);

        $today = now(config('app.timezone'))->toDateString();
        $currentTime = now(config('app.timezone'))->format('H:i:s');

        \App\Models\Schedule::where('mentor_id', $id)
            ->whereNotNull('date')
            ->where(function ($query) use ($today, $currentTime) {
                $query->where('date', '<', $today)
                    ->orWhere(function ($sameDay) use ($today, $currentTime) {
                        $sameDay->where('date', $today)
                            ->where('end_time', '<=', $currentTime);
                    });
            })
            ->delete();

        // Calculate average rating
        $avgRating = $mentor->feedbackReceived()->avg('rating');
        $mentor->rating = $avgRating ? round($avgRating, 2) : null;
        $mentor->total_reviews = $mentor->feedbackReceived()->count();

        // Get available schedules for next 14 days
        $availableSchedules = \App\Models\Schedule::where('mentor_id', $id)
            ->where('is_available', true)
            ->where('date', '>=', now()->format('Y-m-d'))
            ->where('date', '<=', now()->addDays(14)->format('Y-m-d'))
            ->where(function ($query) use ($today, $currentTime) {
                $query->where('date', '>', $today)
                    ->orWhere(function ($sameDay) use ($today, $currentTime) {
                        $sameDay->where('date', $today)
                            ->where('start_time', '>', $currentTime);
                    });
            })
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
            ->where(function ($query) use ($today, $currentTime) {
                $query->where('date', '>', $today)
                    ->orWhere(function ($sameDay) use ($today, $currentTime) {
                        $sameDay->where('date', $today)
                            ->where('start_time', '>', $currentTime);
                    });
            })
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

        // 6. Pending requests
        $pendingRequests = $user->mentorships()
            ->where('status', 'pending')
            ->count();

        $monthlyEarnings = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = \Carbon\Carbon::now()->subMonths($i);
            $monthName = $month->format('M');
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $monthEarnings = $user->mentorships()
                ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
                ->where('appointments.status', 'completed')
                ->whereBetween('appointments.updated_at', [$monthStart, $monthEnd])
                ->sum('appointments.fee');

            $monthlyEarnings[] = [
                'name' => $monthName,
                'earnings' => (float)($monthEarnings ?: 0),
            ];
        }

        // 8. Recent Transactions
        $recentTransactions = $user->mentorships()
            ->join('appointments', 'mentorships.id', '=', 'appointments.mentorship_id')
            ->join('users as mentee', 'mentorships.mentee_id', '=', 'mentee.id')
            ->where('appointments.status', 'completed')
            ->orderBy('appointments.updated_at', 'desc')
            ->select('appointments.id', 'appointments.updated_at as date', 'appointments.fee as amount', 'mentee.name as mentee_name')
            ->get();

        return response()->json([
            'total_mentees' => $totalMentees,
            'hours_taught' => $hoursProvided,
            'total_earnings' => $earnings,
            'rating' => $rating,
            'upcoming_sessions' => $upcomingSessions,
            'pending_requests' => $pendingRequests,
            'monthly_earnings' => $monthlyEarnings,
            'recent_transactions' => $recentTransactions,
        ]);
    }

    public function getNearby(Request $request) {
        $validated = $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:5000',
        ]);

        $originLat = isset($validated['lat']) ? (float) $validated['lat'] : 3.1390;
        $originLng = isset($validated['lng']) ? (float) $validated['lng'] : 101.6869;
        $radiusKm = isset($validated['radius_km']) ? (float) $validated['radius_km'] : 30.0;

        $mentors = User::where('role', 'mentor')
            ->where('is_active', true)
            ->with('mentorProfile')
            ->get();

        $fakeLocations = [
            ['address' => 'Jalan Ampang, Kuala Lumpur, 50450', 'lat' => 3.158, 'lng' => 101.714],
            ['address' => 'SS15, Subang Jaya, Selangor, 47500', 'lat' => 3.076, 'lng' => 101.589],
            ['address' => 'Georgetown, Penang, 10200', 'lat' => 5.416, 'lng' => 100.332],
            ['address' => 'Johor Bahru City Centre, Johor, 80000', 'lat' => 1.492, 'lng' => 103.741],
            ['address' => 'Kota Kinabalu, Sabah, 88000', 'lat' => 5.980, 'lng' => 116.073],
            ['address' => 'Kuching Waterfront, Sarawak, 93000', 'lat' => 1.553, 'lng' => 110.344],
            ['address' => 'Cyberjaya, Selangor, 63000', 'lat' => 2.923, 'lng' => 101.653],
            ['address' => 'Ipoh Garden East, Perak, 31400', 'lat' => 4.597, 'lng' => 101.090],
        ];

        $nearby = $mentors->map(function ($mentor) use ($originLat, $originLng, $fakeLocations) {
            $lat = is_numeric($mentor->latitude ?? null) ? (float) $mentor->latitude : null;
            $lng = is_numeric($mentor->longitude ?? null) ? (float) $mentor->longitude : null;

            $fakeLocation = $fakeLocations[$mentor->id % count($fakeLocations)];
            $fakeAddress = $fakeLocation['address'];

            // Fallback: use actual coordinates of the fake address if mentor has no saved coordinates
            if ($lat === null || $lng === null) {
                // Add a very tiny deterministic offset so multiple mentors in same city don't completely overlap
                $seed = crc32((string) $mentor->id);
                $latOffset = ((($seed % 1000) / 1000) - 0.5) * 0.01;
                $lngOffset = ((((int) ($seed / 1000) % 1000) / 1000) - 0.5) * 0.01;
                $lat = $fakeLocation['lat'] + $latOffset;
                $lng = $fakeLocation['lng'] + $lngOffset;
            }

            $earthRadiusKm = 6371;
            $dLat = deg2rad($lat - $originLat);
            $dLng = deg2rad($lng - $originLng);
            $a = sin($dLat / 2) * sin($dLat / 2)
                + cos(deg2rad($originLat)) * cos(deg2rad($lat))
                * sin($dLng / 2) * sin($dLng / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $distanceKm = $earthRadiusKm * $c;

            $mentorProfile = $mentor->mentorProfile;

            return [
                'id' => $mentor->id,
                'name' => $mentor->name,
                'bio' => $mentor->bio,
                'address' => $mentor->address ?: $fakeAddress,
                'fake_address' => $fakeAddress,
                'skills' => $mentor->skills ?? [],
                'title' => $mentorProfile?->job_title,
                'hourly_rate' => $mentorProfile?->hourly_rate,
                'rating' => $mentor->feedbackReceived()->avg('rating') ? round($mentor->feedbackReceived()->avg('rating'), 2) : null,
                'reviews' => $mentor->feedbackReceived()->count(),
                'latitude' => round($lat, 6),
                'longitude' => round($lng, 6),
                'distance_km' => round($distanceKm, 2),
            ];
        })
            ->filter(fn ($mentor) => $mentor['distance_km'] <= $radiusKm)
            ->sortBy('distance_km')
            ->values();

        return response()->json([
            'origin' => [
                'latitude' => $originLat,
                'longitude' => $originLng,
            ],
            'radius_km' => $radiusKm,
            'data' => $nearby,
        ]);
    }

    /**
     * Get all unique skills available across all mentors.
     * Used by the skill selection screen to show real mentor skills.
     */
    public function getAllSkills()
    {
        $mentors = User::where('role', 'mentor')
            ->where('is_active', true)
            ->with('mentorProfile')
            ->get();
            
        $allSkills = [];
        foreach ($mentors as $mentor) {
            $skills = is_string($mentor->skills) ? json_decode($mentor->skills, true) : $mentor->skills;
            if (is_array($skills)) {
                $allSkills = array_merge($allSkills, $skills);
            }
            
            if ($mentor->mentorProfile) {
                $expertise = is_string($mentor->mentorProfile->expertise_areas) ? json_decode($mentor->mentorProfile->expertise_areas, true) : $mentor->mentorProfile->expertise_areas;
                if (is_array($expertise)) {
                    $allSkills = array_merge($allSkills, $expertise);
                }
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