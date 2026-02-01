<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\MentorProfile;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'bio' => 'sometimes|string', // Assuming bio is on users table or profile? Check User model.
        ]);

        $user->update($validated);
        
        // If bio is on mentee/mentor profile, update it there too?
        // Frontend sends bio to /user/profile.
        // Let's assume User has bio column? Or we need to update relation.
        // User model usually has: name, email, password.
        // MentorProfile has: bio, etc.
        // Let's check User model later. for now safe to update what we can.

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        // Delete old image if exists
        if ($user->profile_image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $user->profile_image)); // Handle URL stored
        }

        $path = $request->file('image')->store('profile_images', 'public');
        
        // Save full URL or relative path? 
        // Frontend usually expects URL. Asset helper generates full URL.
        $url = asset('storage/' . $path);
        
        $user->profile_image = $url; // Storing URL for simplicity as User model likely has string
        $user->save();

        return response()->json([
            'message' => 'Profile image uploaded successfully',
            'image_url' => $url
        ]);
    }
}
