<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\MentorProfile;
use App\Models\MenteeProfile;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:1000',
            'skills' => 'sometimes|array',
            'skills.*' => 'string|max:100',
            'interests' => 'sometimes|array',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()->load(['mentorProfile', 'menteeProfile']),
            'profile_complete' => $user->fresh()->isProfileComplete()
        ]);
    }

    public function updateMentor(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMentor()) {
            return response()->json(['message' => 'Not a mentor'], 403);
        }

        $profile = $user->mentorProfile;
        
        if (!$profile) {
             // Create if missing?
             $profile = MentorProfile::create(['user_id' => $user->id]);
        }

        $validated = $request->validate([
            'job_title' => 'sometimes|string',
            'company' => 'sometimes|string',
            'years_of_experience' => 'sometimes|integer',
            'expertise_areas' => 'sometimes|array',
            'industry' => 'sometimes|string',
            'hourly_rate' => 'sometimes|numeric',
            'mentorship_approach' => 'sometimes|string',
            'is_available' => 'sometimes|boolean',
        ]);

        $profile->update($validated);

        return response()->json([
            'message' => 'Mentor profile updated successfully',
            'profile' => $profile
        ]);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = $request->user();

        // Delete old image if exists
        if ($user->profile_image) {
            $oldPath = str_replace('/storage/', '', $user->profile_image);
            $oldPath = str_replace(config('app.url') . '/storage/', '', $oldPath);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // Store new image
        $path = $request->file('image')->store('profiles', 'public');
        
        // Generate full URL
        $url = asset('storage/' . $path);
        
        $user->profile_image = $url;
        $user->save();

        return response()->json([
            'message' => 'Profile image uploaded successfully',
            'image_url' => $url,
            'path' => $path
        ]);
    }

    public function completeProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'bio' => 'required|string|max:1000',
            'skills' => 'required|array|min:1',
            'skills.*' => 'string|max:100',
            'interests' => 'sometimes|array',
            'current_skills' => 'sometimes|array',
            'current_skills.*' => 'string|max:100',
            'skills_to_learn' => 'sometimes|array',
            'skills_to_learn.*' => 'string|max:100',
            'career_goals' => 'sometimes|string|max:2000',
            'education_level' => 'sometimes|string|max:255',
            'field_of_study' => 'sometimes|string|max:255',
        ]);

        $user->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'bio' => $validated['bio'],
            'skills' => $validated['skills'],
            'interests' => $validated['interests'] ?? $user->interests,
        ]);

        if ($user->isMentee()) {
            $profile = $user->menteeProfile;
            if (!$profile) {
                $profile = MenteeProfile::create(['user_id' => $user->id]);
            }

            $profile->update([
                'current_skills' => $validated['current_skills'] ?? $validated['skills'],
                'skills_to_learn' => $validated['skills_to_learn'] ?? $profile->skills_to_learn,
                'career_goals' => $validated['career_goals'] ?? $profile->career_goals,
                'education_level' => $validated['education_level'] ?? $profile->education_level,
                'field_of_study' => $validated['field_of_study'] ?? $profile->field_of_study,
            ]);
        }

        return response()->json([
            'message' => 'Profile completed successfully',
            'user' => $user->fresh()->load(['mentorProfile', 'menteeProfile']),
            'profile_complete' => $user->fresh()->isProfileComplete()
        ]);
    }
}
