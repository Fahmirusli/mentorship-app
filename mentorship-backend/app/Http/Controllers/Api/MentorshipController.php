<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mentorship;
use Illuminate\Http\Request;

class MentorshipController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Mentorship::with(['mentor', 'mentee', 'appointments']);

        if ($user->isMentor()) {
            $query->where('mentor_id', $user->id);
        } elseif ($user->isMentee()) {
            $query->where('mentee_id', $user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $mentorships = $query->orderBy('created_at', 'desc')->get();

        return response()->json($mentorships);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mentor_id' => 'required|exists:users,id',
            'goals' => 'nullable|string',
        ]);

        // Check if user is mentee
        if (!$request->user()->isMentee()) {
            return response()->json([
                'message' => 'Only mentees can request mentorships',
            ], 403);
        }

        // Check if mentorship already exists
        $existing = Mentorship::where('mentor_id', $validated['mentor_id'])
            ->where('mentee_id', $request->user()->id)
            ->whereIn('status', ['pending', 'active'])
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'You already have a mentorship request with this mentor',
            ], 400);
        }

        $mentorship = Mentorship::create([
            'mentor_id' => $validated['mentor_id'],
            'mentee_id' => $request->user()->id,
            'goals' => $validated['goals'] ?? null,
            'status' => 'pending',
        ]);



        return response()->json([
            'message' => 'Mentorship request created successfully',
            'mentorship' => $mentorship->load(['mentor', 'mentee']),
        ], 201);
    }

    public function show($id)
    {
        $mentorship = Mentorship::with(['mentor', 'mentee', 'appointments', 'feedback'])
            ->findOrFail($id);

        // Authorization check
        $user = auth()->user();
        if ($mentorship->mentor_id !== $user->id && 
            $mentorship->mentee_id !== $user->id && 
            !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($mentorship);
    }

    public function update(Request $request, $id)
    {
        $mentorship = Mentorship::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,active,completed,cancelled',
            'goals' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
        ]);

        // Authorization: Only mentor can approve/reject, mentee can cancel
        $user = $request->user();
        if ($mentorship->mentor_id !== $user->id && 
            $mentorship->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mentorship->update($validated);



        // Update mentor's total_mentees count if status changed to active
        if (isset($validated['status']) && $validated['status'] === 'active') {
            $mentor = $mentorship->mentor;
            if ($mentor->mentorProfile) {
                $mentor->mentorProfile->increment('total_mentees');
            }
        }

        return response()->json([
            'message' => 'Mentorship updated successfully',
            'mentorship' => $mentorship->load(['mentor', 'mentee']),
        ]);
    }

    public function destroy($id)
    {
        $mentorship = Mentorship::findOrFail($id);

        // Authorization
        $user = auth()->user();
        if ($mentorship->mentee_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mentorship->delete();

        return response()->json([
            'message' => 'Mentorship deleted successfully',
        ]);
    }
}

