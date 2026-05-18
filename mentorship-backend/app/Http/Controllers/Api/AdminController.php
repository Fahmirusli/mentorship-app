<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mentorship;
use App\Models\Appointment;
use App\Models\Job;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->user()->role !== 'admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return $next($request);
        });
    }

    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_mentors' => User::where('role', 'mentor')->count(),
            'total_mentees' => User::where('role', 'mentee')->count(),
            'active_mentorships' => Mentorship::where('status', 'active')->count(),
            'pending_mentorships' => Mentorship::where('status', 'pending')->count(),
            'completed_mentorships' => Mentorship::where('status', 'completed')->count(),
            'total_appointments' => Appointment::count(),
            'upcoming_appointments' => Appointment::where('scheduled_at', '>', now())
                ->where('status', 'scheduled')
                ->count(),
            'total_jobs' => Job::where('is_active', true)->count(),
            'total_feedback' => Feedback::count(),
            'average_rating' => round(Feedback::avg('rating'), 2),
            'total_revenue' => \App\Models\Transaction::where('status', 'paid')->sum('amount'),
        ];

        // Recent activities
        $recentMentorships = Mentorship::with(['mentor', 'mentee'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        $recentUsers = User::orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Monthly Stats (Last 6 months)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthName = $month->format('M');
            $year = $month->year;
            $monthNum = $month->month;

            $monthlyStats[] = [
                'name' => $monthName,
                'users' => User::whereYear('created_at', $year)->whereMonth('created_at', $monthNum)->count(),
                'mentorships' => Mentorship::whereYear('created_at', $year)->whereMonth('created_at', $monthNum)->count(),
                // 'revenue': Assuming simpler model for now, maybe sessions * rate? 
                // Let's stick to concrete counts for now to ensure "real data" accuracy.
            ];
        }

        // Job Stats by Source
        $jobStats = Job::select('source', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        return response()->json([
            'stats' => $stats,
            'recent_mentorships' => $recentMentorships,
            'recent_users' => $recentUsers,
            'monthly_stats' => $monthlyStats,
            'job_stats' => $jobStats,
        ]);
    }

    public function getUsers(Request $request)
    {
        $query = User::with(['mentorProfile', 'menteeProfile'])
            ->select('users.*');

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Filter by verification
        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->is_verified);
        }



        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 20));

        return response()->json($users);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:admin,mentor,mentee',
            'is_active' => 'sometimes|boolean',
            'is_verified' => 'sometimes|boolean',
            'phone' => 'sometimes|string|max:20',
            'password' => 'sometimes|string|min:8',

        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Ensure boolean conversion for is_verified
        if ($request->has('is_verified')) {
            $validated['is_verified'] = filter_var($request->is_verified, FILTER_VALIDATE_BOOLEAN);
            
            // Set verified_at timestamp when verified
            if ($validated['is_verified'] && !$user->is_verified) {
                $validated['verified_at'] = now();
            }
            
            // Clear verified_at when unverified
            if (!$validated['is_verified']) {
                $validated['verified_at'] = null;
            }
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->fresh(),
        ]);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function getMentorships(Request $request)
    {
        $query = Mentorship::with(['mentor', 'mentee']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $mentorships = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json($mentorships);
    }

    public function verifyUser($id)
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);



        return response()->json([
            'message' => 'User verified successfully',
            'user' => $user,
        ]);
    }

    public function unverifyUser($id)
    {
        $user = User::findOrFail($id);
        
        $user->update([
            'is_verified' => false,
            'verified_at' => null,
        ]);

        return response()->json([
            'message' => 'User verification removed',
            'user' => $user,
        ]);
    }
}
