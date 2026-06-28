<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Mentorship;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Feedback::with(['fromUser', 'toUser', 'mentorship']);

        // Get feedback for or from the user
        $query->where('from_user_id', $user->id)
              ->orWhere('to_user_id', $user->id);

        $feedback = $query->orderBy('created_at', 'desc')->get();

        return response()->json($feedback);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mentorship_id' => 'required|exists:mentorships,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'to_user_id' => 'required|exists:users,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $mentorship = Mentorship::findOrFail($validated['mentorship_id']);

        // Authorization: User must be part of the mentorship
        $user = $request->user();
        if ($mentorship->mentor_id !== $user->id && $mentorship->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if user already gave feedback for this appointment/mentorship
        $existingFeedback = Feedback::where('mentorship_id', $validated['mentorship_id'])
            ->where('from_user_id', $user->id)
            ->where('to_user_id', $validated['to_user_id']);

        if (isset($validated['appointment_id'])) {
            $appointment = \App\Models\Appointment::find($validated['appointment_id']);
            if (!$appointment || $appointment->status !== 'completed') {
                return response()->json([
                    'message' => 'Feedback can only be given for completed sessions. If missed, please reschedule.'
                ], 403);
            }
            $existingFeedback->where('appointment_id', $validated['appointment_id']);
        }

        if ($existingFeedback->exists()) {
            return response()->json([
                'message' => 'You have already provided feedback for this session',
            ], 400);
        }

        $feedback = Feedback::create([
            'mentorship_id' => $validated['mentorship_id'],
            'appointment_id' => $validated['appointment_id'] ?? null,
            'from_user_id' => $user->id,
            'to_user_id' => $validated['to_user_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        // Update mentor's rating if feedback is for a mentor
        $toUser = \App\Models\User::find($validated['to_user_id']);
        if ($toUser->isMentor() && $toUser->mentorProfile) {
            $avgRating = Feedback::where('to_user_id', $toUser->id)->avg('rating');
            $toUser->mentorProfile->update(['rating' => round($avgRating, 2)]);
        }

        return response()->json([
            'message' => 'Feedback submitted successfully',
            'feedback' => $feedback->load(['fromUser', 'toUser']),
        ], 201);
    }

    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);

        // Authorization: Only the person who gave feedback or admin can delete
        $user = auth()->user();
        if ($feedback->from_user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $feedback->delete();

        return response()->json([
            'message' => 'Feedback deleted successfully',
        ]);
    }
}
