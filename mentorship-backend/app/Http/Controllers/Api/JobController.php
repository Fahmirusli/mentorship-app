<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\JobMatchingService;
use App\Services\JobScraperService;
use Illuminate\Http\Request;

class JobController extends Controller
{
    protected $jobMatchingService;
    
    public function __construct(JobMatchingService $jobMatchingService)
    {
        $this->jobMatchingService = $jobMatchingService;
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'mentor') { // Assuming mentors can post too? Or just admin? Let's say Admin for now based on user request.
             // Actually user said "mentorship, mentee, and mentor jobs". Usually admin manages jobs.
             if (auth()->user()->role !== 'admin') {
                 return response()->json(['message' => 'Unauthorized'], 403);
             }
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'description' => 'required|string',
            'requirements' => 'nullable|array',
            'location' => 'nullable|string',
            'salary' => 'nullable|string',
            'source' => 'nullable|string',
            'external_url' => 'nullable|url',
            'job_type' => 'nullable|string',
            'experience_level' => 'nullable|string',
        ]);

        $job = Job::create(array_merge($validated, ['is_active' => true, 'posted_date' => now()]));

        return response()->json($job, 201);
    }

    public function update(Request $request, $id)
    {
         if (auth()->user()->role !== 'admin') {
             return response()->json(['message' => 'Unauthorized'], 403);
         }

         $job = Job::findOrFail($id);
         
         $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'company' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'requirements' => 'nullable|array',
            'location' => 'nullable|string',
            'salary' => 'nullable|string',
            'source' => 'nullable|string',
            'external_url' => 'nullable|url',
            'is_active' => 'sometimes|boolean',
         ]);

         $job->update($validated);

         return response()->json($job);
    }

    public function destroy($id)
    {
         if (auth()->user()->role !== 'admin') {
             return response()->json(['message' => 'Unauthorized'], 403);
         }

         $job = Job::findOrFail($id);
         $job->delete();

         return response()->json(['message' => 'Job deleted successfully']);
    }

    public function index(Request $request)
    {
        $query = Job::where('is_active', true);

        // Search by title, company, or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by skills
        if ($request->has('skills')) {
            $skills = is_array($request->skills) ? $request->skills : [$request->skills];
            foreach ($skills as $skill) {
                $query->whereJsonContains('requirements', $skill); // Changed to 'requirements' assuming JSON column or similar search
            }
        }

        // Filter by location
        if ($request->has('location')) {
            $query->where('location', 'like', "%{$request->location}%");
        }

        // Filter by job type
        if ($request->has('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        // Filter by experience level
        if ($request->has('experience_level')) {
            $query->where('experience_level', $request->experience_level);
        }

        // Filter by source
        if ($request->has('source')) {
            $query->where('source', $request->source);
        } elseif ($request->has('source_platform')) {
             $query->where('source', $request->source_platform);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'posted_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $jobs = $query->paginate($request->get('per_page', 50)); // Increased Limit

        return response()->json($jobs);
    }

    public function show($id)
    {
        $job = Job::findOrFail($id);
        
        $matchAnalysis = null;
        if (auth()->check() && auth()->user()->role === 'mentee') {
            $matchAnalysis = $this->jobMatchingService->analyzeJobMatch(auth()->id(), $id);
        }
        
        return response()->json([
            'job' => $job,
            'match_analysis' => $matchAnalysis
        ]);
    }

    public function recommendations(Request $request){
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Delegate to the robust service
        $recommendations = $this->jobMatchingService->getRecommendations($user->id);

        return response()->json([
            'recommendations' => $recommendations
        ]);
    }

        public function triggerScrape(Request $request, JobScraperService $scraper)
    {
        // Only admins can trigger manual scraping
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $keyword = $request->input('keyword', 'Software Engineer');
        $results = $scraper->scrapeAll($keyword);
        
        return response()->json([
            'message' => "Scraping completed for '$keyword'",
            'results' => $results
        ]);
    }
}
