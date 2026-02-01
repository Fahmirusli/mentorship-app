<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Job;
use App\Models\Mentorship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Appointment;

class AdminController extends Controller
{
    // ... existing constructor ...

    public function revenue()
    {
        $revenues = \App\Models\Transaction::with(['user', 'appointment.mentor'])
            ->latest()
            ->paginate(20);
        
        $totalRevenue = \App\Models\Transaction::where('status', 'paid')->sum('amount');

        return view('admin.revenue', compact('revenues', 'totalRevenue'));
    }
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->role !== 'admin') {
                abort(403, 'Unauthorized access');
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
            'total_jobs' => Job::count(),
            'active_mentorships' => Mentorship::where('status', 'active')->count(),
            'total_feedbacks' => \App\Models\Feedback::count(),
        ];

        // Monthly Stats (Last 6 months)
        $monthlyStats = [];
        $months = [];
        $userCounts = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthName = $month->format('M j');
            $year = $month->year;
            $monthNum = $month->month;

            $months[] = $month->format('M');
            
            // Simplified: User signups in this month
            $userCounts[] = User::whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum)
                ->count();
        }

        // Job Stats by Source
        $jobStats = Job::select('source', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // Recent mentorships
        $recentMentorships = Mentorship::with(['mentor', 'mentee'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'months', 'userCounts', 'jobStats', 'recentMentorships'));
    }

    public function users()
    {
        $users = User::latest()->paginate(20);
        return view('admin.users', compact('users'));
    }

    public function mentees()
    {
        $mentees = User::where('role', 'mentee')->latest()->paginate(20);
        return view('admin.mentees', compact('mentees'));
    }

    public function mentors()
    {
        $mentors = User::where('role', 'mentor')->latest()->paginate(20);
        return view('admin.mentors', compact('mentors'));
    }

    public function mentorships()
    {
        $mentorships = Mentorship::with(['mentor', 'mentee'])
            ->latest()
            ->paginate(20);
            
        // Get lists for the "Create/Edit" dropdowns
        $allMentors = User::where('role', 'mentor')->get(['id', 'name']);
        $allMentees = User::where('role', 'mentee')->get(['id', 'name']);

        return view('admin.mentorships', compact('mentorships', 'allMentors', 'allMentees'));
    }

    public function feedbacks()
    {
        $feedbacks = \App\Models\Feedback::with(['fromUser', 'toUser', 'mentorship'])
            ->latest()
            ->paginate(20);
            
        return view('admin.feedbacks', compact('feedbacks'));
    }

    public function deleteFeedback($id)
    {
        $feedback = \App\Models\Feedback::findOrFail($id);
        $feedback->delete();
        return back()->with('success', 'Feedback deleted successfully.');
    }

    public function storeMentorship(Request $request)
    {
        $validated = $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'mentee_id' => 'required|exists:users,id|different:mentor_id',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
        ]);

        Mentorship::create($validated);

        return back()->with('success', 'Mentorship session created successfully.');
    }

    public function updateMentorship(Request $request, $id)
    {
        $mentorship = Mentorship::findOrFail($id);
        
        $validated = $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'mentee_id' => 'required|exists:users,id|different:mentor_id',
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
        ]);

        $mentorship->update($validated);

        return back()->with('success', 'Mentorship session updated successfully.');
    }

    public function deleteMentorship($id)
    {
        $mentorship = Mentorship::findOrFail($id);
        $mentorship->delete();
        return back()->with('success', 'Mentorship session deleted successfully.');
    }

    public function jobs(Request $request)
    {
        $query = Job::latest();
        
        // Filtering
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        
        $jobs = $query->paginate(20);
        
        $jobStats = [
            'total' => Job::count(),
            'by_source' => Job::selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
                ->get()
        ];
        return view('admin.jobs', compact('jobs', 'jobStats'));
    }

    public function toggleVisibility($id)
    {
        $job = Job::findOrFail($id);
        $job->is_active = !$job->is_active;
        $job->save();
        
        $status = $job->is_active ? 'visible' : 'hidden';
        return back()->with('success', "Job is now $status to users.");
    }

    public function scrapeJobs(Request $request)
    {
        try {
            $keyword = $request->input('keyword', 'Software Engineer');
            $scraperService = new \App\Services\JobScraperService();
            $scraperService->scrapeAll($keyword);

            return back()->with('success', "Job scraping for '$keyword' started successfully!");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to start scraping: ' . $e->getMessage());
        }
    }



    // CRUD: Users (Refactored to handle generic user deletion safely)
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        if ($user->role === 'admin') {
            return back()->with('error', 'Cannot delete admin users.');
        }
        
        // Optional: Delete related mentorships or jobs if cascading is crucial, 
        // but foreign keys usually handle this or we soft delete. 
        // For now, standard delete.
        $user->delete();
        
        return back()->with('success', 'User deleted successfully');
    }

    // CRUD: Users
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:mentee,mentor,admin'
        ]);

        $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        User::create($validated);

        return back()->with('success', 'User created successfully.');
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'role' => 'required|in:mentee,mentor,admin'
        ]);

        $user->update($validated);
        return back()->with('success', 'User updated successfully.');
    }

    // CRUD: Jobs
    public function storeJob(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'location' => 'required|string',
            'salary' => 'nullable|string',
            'is_active' => 'boolean',
            'source' => 'nullable|string'
        ]);
        
        // Defaults
        $validated['source'] = $validated['source'] ?? 'Manual';
        $validated['posted_date'] = now();
        $validated['description'] = 'Manually created job via Admin Dashboard.'; // Fallback

        Job::create($validated);
        return back()->with('success', 'Job created successfully.');
    }

    public function updateJob(Request $request, $id)
    {
        $job = Job::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'location' => 'required|string',
            'salary' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $job->update($validated);
        return back()->with('success', 'Job updated successfully.');
    }

    public function deleteJob($id)
    {
        $job = Job::findOrFail($id);
        $job->delete();
        return back()->with('success', 'Job deleted successfully');
    }
}
