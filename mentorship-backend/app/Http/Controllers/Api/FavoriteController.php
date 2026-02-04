<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * Get user's favorite mentors
     */
    public function index()
    {
        $user = auth()->user();
        
        $favorites = DB::table('favorites')
            ->where('user_id', $user->id)
            ->pluck('mentor_id');

        $mentors = User::whereIn('id', $favorites)
            ->where('role', 'mentor')
            ->with('mentorProfile')
            ->get();

        return response()->json($mentors);
    }

    /**
     * Toggle favorite mentor
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'mentor_id' => 'required|exists:users,id'
        ]);

        $user = auth()->user();
        $mentorId = $request->mentor_id;

        // Check if already favorited
        $exists = DB::table('favorites')
            ->where('user_id', $user->id)
            ->where('mentor_id', $mentorId)
            ->exists();

        if ($exists) {
            // Remove from favorites
            DB::table('favorites')
                ->where('user_id', $user->id)
                ->where('mentor_id', $mentorId)
                ->delete();

            return response()->json([
                'message' => 'Removed from favorites',
                'is_favorited' => false
            ]);
        } else {
            // Add to favorites
            DB::table('favorites')->insert([
                'user_id' => $user->id,
                'mentor_id' => $mentorId,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return response()->json([
                'message' => 'Added to favorites',
                'is_favorited' => true
            ]);
        }
    }
}
