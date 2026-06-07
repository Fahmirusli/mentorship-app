<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    /**
     * Upload Profile Picture
     */
    public function uploadProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        /** @var User $user */
        $user = User::find(auth()->id());

        $file = $request->file('image');
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $contents = base64_encode(file_get_contents($file->getRealPath()));
        $url = "data:{$mime};base64,{$contents}";

        // Update user
        $user->profile_image = $url;
        $user->save();

        return response()->json([
            'message' => 'Profile image uploaded successfully',
            'image_url' => $url,
            'stored_in' => 'database'
        ]);
    }

    /**
     * Upload Resume
     */
    public function uploadResume(Request $request)
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx|max:5120'
        ]);

        /** @var User $user */
        $user = User::find(auth()->id());

        $file = $request->file('resume');
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $contents = base64_encode(file_get_contents($file->getRealPath()));
        $dataUrl = "data:{$mime};base64,{$contents}";

        $user->update([
            'resume_path' => $dataUrl
        ]);

        return response()->json([
            'message' => 'Resume uploaded successfully',
            'resume_url' => $dataUrl,
            'resume_path' => $dataUrl,
            'stored_in' => 'database'
        ]);
    }

    /**
     * Update User Skills
     */
    public function updateSkills(Request $request)
    {
        $request->validate([
            'skills' => 'required|array',
            'skills.*' => 'string'
        ]);

        /** @var User $user */
        $user = User::find(auth()->id());
        $user->update([
            'skills' => json_encode($request->skills)
        ]);

        return response()->json([
            'message' => 'Skills updated successfully',
            'skills' => $request->skills
        ]);
    }

    /**
     * Get Recommended Jobs
     */
    public function getRecommendedJobs()
    {
        $user = auth()->user();
        
        if (!$user->skills) {
            return response()->json([
                'message' => 'Please add your skills first',
                'jobs' => []
            ]);
        }

        $userSkills = json_decode($user->skills, true) ?? [];

        // Get all jobs
        $jobs = \App\Models\Job::where('active', true)
            ->with('company')
            ->get();

        // Score each job based on skill match
        $recommendedJobs = $jobs->map(function ($job) use ($userSkills) {
            $jobSkills = $job->required_skills ? json_decode($job->required_skills, true) : [];
            
            // Calculate match percentage
            if (empty($jobSkills)) {
                $matchPercentage = 0;
            } else {
                $matches = count(array_intersect(
                    array_map('strtolower', $userSkills),
                    array_map('strtolower', $jobSkills)
                ));
                $matchPercentage = ($matches / count($jobSkills)) * 100;
            }

            return [
                'job' => $job,
                'match_percentage' => round($matchPercentage, 2),
                'matched_skills' => array_intersect(
                    array_map('strtolower', $userSkills),
                    array_map('strtolower', $jobSkills)
                ),
                'missing_skills' => array_diff(
                    array_map('strtolower', $jobSkills),
                    array_map('strtolower', $userSkills)
                )
            ];
        })->filter(function ($item) {
            return $item['match_percentage'] > 0;
        })->sortByDesc('match_percentage')->take(10);

        return response()->json([
            'message' => 'Recommended jobs based on your skills',
            'recommended_jobs' => $recommendedJobs->values()
        ]);
    }
}
