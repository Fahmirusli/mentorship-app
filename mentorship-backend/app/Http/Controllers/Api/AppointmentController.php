<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Mentorship;
use App\Models\WalletTransaction;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    private const UPCOMING_STATUSES = ['scheduled', 'pending_payment', 'rescheduled'];

    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Appointment::with(['mentorship.mentor', 'mentorship.mentee', 'feedback']);

        // Get appointments for user's mentorships
        $mentorshipIds = Mentorship::where('mentor_id', $user->id)
            ->orWhere('mentee_id', $user->id)
            ->pluck('id');

        $query->whereIn('mentorship_id', $mentorshipIds);

        // Filter by status
        if ($request->has('status')) {
            $status = (string) $request->status;
            if ($status === 'upcoming') {
                $query->whereIn('status', self::UPCOMING_STATUSES)
                    ->where('scheduled_at', '>=', now());
            } else {
                $query->where('status', $status);
            }
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('scheduled_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('scheduled_at', '<=', $request->to_date);
        }

        $appointments = $query->orderBy('scheduled_at', 'asc')->get()
            ->map(function (Appointment $appointment) use ($user) {
                $scheduledAt = $appointment->scheduled_at instanceof Carbon
                    ? $appointment->scheduled_at
                    : Carbon::parse($appointment->scheduled_at);

                $mentorship = $appointment->mentorship;
                $mentor = $mentorship?->mentor;
                $mentee = $mentorship?->mentee;

                return [
                    'id' => $appointment->id,
                    'mentorship_id' => $appointment->mentorship_id,
                    'mentor_id' => $mentor?->id,
                    'mentee_id' => $mentee?->id,
                    'mentor_name' => $mentor?->name,
                    'mentee_name' => $mentee?->name,
                    'date' => $scheduledAt->format('Y-m-d'),
                    'time' => $scheduledAt->format('h:i A'),
                    'scheduled_at' => $scheduledAt->toIso8601String(),
                    'duration_minutes' => $appointment->duration_minutes,
                    'status' => $appointment->status,
                    'meeting_link' => $this->normalizeMeetingLink($appointment->meeting_link, $appointment->id),
                    'notes' => $appointment->notes,
                    'title' => $appointment->notes ?: 'Mentorship Session',
                    'is_upcoming' => in_array((string) $appointment->status, self::UPCOMING_STATUSES, true) && $scheduledAt->isFuture(),
                    'mentorship_detail' => [
                        'goal' => $mentorship?->goals,
                        'status' => $mentorship?->status,
                        'start_date' => optional($mentorship?->start_date)->format('Y-m-d'),
                        'end_date' => optional($mentorship?->end_date)->format('Y-m-d'),
                        'other_party_name' => $user->id === $mentor?->id ? $mentee?->name : $mentor?->name,
                        'other_party_id' => $user->id === $mentor?->id ? $mentee?->id : $mentor?->id,
                    ],
                    'has_feedback' => $appointment->feedback->where('from_user_id', $user->id)->isNotEmpty(),
                    'mentorship' => $mentorship,
                ];
            })
            ->values();

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mentorship_id' => 'required|exists:mentorships,id',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:180',
            'meeting_link' => 'nullable|string|max:2048',
            'notes' => 'nullable|string',
        ]);

        $mentorship = Mentorship::findOrFail($validated['mentorship_id']);

        // Authorization check
        $user = $request->user();
        if ($mentorship->mentor_id !== $user->id && $mentorship->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $mentorId = $mentorship->mentor_id;
        $appointmentStart = \Carbon\Carbon::parse($validated['scheduled_at']);
        $appointmentEnd = $appointmentStart->copy()->addMinutes($validated['duration_minutes']);
        $dayOfWeek = $appointmentStart->dayOfWeekIso; // 1 (Monday) to 7 (Sunday). Model might use 0-6 or 1-7. Code used 0-6 in controller validation? 
        // Controller validation for schedule used 0-6? "day_of_week" => "nullable|integer|between(0,6)"
        // Carbon dayOfWeek returns 0 (Sunday) - 6 (Saturday).
        $carbonDay = $appointmentStart->dayOfWeek; 
        
        $dateStr = $appointmentStart->format('Y-m-d');
        $timeStr = $appointmentStart->format('H:i:s');
        $endTimeStr = $appointmentEnd->format('H:i:s');

        // 1. Check if slot falls within a defined schedule
        // Priorities: Specific Date > Recurring Day
        $schedule = \App\Models\Schedule::where('mentor_id', $mentorId)
            ->where(function($q) use ($dateStr, $carbonDay) {
                $q->where('date', $dateStr)
                  ->orWhere(function($sub) use ($carbonDay) {
                      $sub->whereNull('date')->where('day_of_week', $carbonDay);
                  });
            })
            ->where('is_available', true)
            ->where('start_time', '<=', $timeStr)
            ->where('end_time', '>=', $endTimeStr) // Simple check: appointment must fit WITHIN slot
            ->orderByRaw('date IS NOT NULL DESC') // Prefer specific date
            ->first();

        if (!$schedule) {
             return response()->json(['message' => 'Mentor is not available at this time.'], 400);
        }

        // 2. Check for conflicting appointments
        $conflict = Appointment::whereHas('mentorship', function($q) use ($mentorId) {
                $q->where('mentor_id', $mentorId);
            })
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($appointmentStart, $appointmentEnd) {
                $q->whereBetween('scheduled_at', [$appointmentStart, $appointmentEnd])
                  ->orWhereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) BETWEEN ? AND ?', [$appointmentStart, $appointmentEnd])
                  ->orWhere(function ($sub) use ($appointmentStart, $appointmentEnd) {
                      $sub->where('scheduled_at', '<=', $appointmentStart)
                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) >= ?', [$appointmentEnd]);
                  });
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'This time slot is already booked.'], 409);
        }

        // Snapshot the fee
        $mentorProfile = $mentorship->mentor->mentorProfile;
        $fee = $mentorProfile ? ($mentorProfile->hourly_rate ?? 50) : 50; // Default 50 if not set

        $appointment = Appointment::create([
            ...$validated,
            'meeting_link' => $this->normalizeInputMeetingLink($validated['meeting_link'] ?? null),
            'fee' => $fee,
        ]);



        return response()->json([
            'message' => 'Appointment created successfully',
            'appointment' => $appointment->load('mentorship'),
        ], 201);
    }

    public function show($id)
    {
        $appointment = Appointment::with(['mentorship.mentor', 'mentorship.mentee', 'feedback'])
            ->findOrFail($id);

        // Authorization
        $user = auth()->user();
        if ($appointment->mentorship->mentor_id !== $user->id && 
            $appointment->mentorship->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($appointment);
    }

    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $validated = $request->validate([
            'scheduled_at' => 'sometimes|date',
            'duration_minutes' => 'sometimes|integer|min:15|max:180',
            'status' => 'sometimes|in:scheduled,completed,cancelled,rescheduled',
            'meeting_link' => 'sometimes|string|max:2048',
            'notes' => 'sometimes|string',
        ]);

        // Authorization
        $user = $request->user();
        if ($appointment->mentorship->mentor_id !== $user->id && 
            $appointment->mentorship->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (array_key_exists('meeting_link', $validated)) {
            $validated['meeting_link'] = $this->normalizeInputMeetingLink($validated['meeting_link']);
        }

        $appointment->update($validated);

        return response()->json([
            'message' => 'Appointment updated successfully',
            'appointment' => $appointment,
        ]);
    }

    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);

        // Authorization
        $user = auth()->user();
        if ($appointment->mentorship->mentee_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }



        $appointment->delete();

        return response()->json([
            'message' => 'Appointment cancelled successfully',
        ]);
    }
    
    public function reschedule(Request $request, $id)
    {
        $appointment = Appointment::with('mentorship')->findOrFail($id);

        // Authorization - both mentor and mentee can reschedule
        $user = $request->user();
        if ($appointment->mentorship->mentor_id !== $user->id && 
            $appointment->mentorship->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'sometimes|integer|min:15|max:180',
            'notes' => 'nullable|string',
        ]);

        $mentorId = $appointment->mentorship->mentor_id;
        $appointmentStart = \Carbon\Carbon::parse($validated['scheduled_at']);
        $duration = $validated['duration_minutes'] ?? $appointment->duration_minutes;
        $appointmentEnd = $appointmentStart->copy()->addMinutes($duration);
        $carbonDay = $appointmentStart->dayOfWeek;
        
        $dateStr = $appointmentStart->format('Y-m-d');
        $timeStr = $appointmentStart->format('H:i:s');
        $endTimeStr = $appointmentEnd->format('H:i:s');

        // Check mentor availability
        $schedule = \App\Models\Schedule::where('mentor_id', $mentorId)
            ->where(function($q) use ($dateStr, $carbonDay) {
                $q->where('date', $dateStr)
                  ->orWhere(function($sub) use ($carbonDay) {
                      $sub->whereNull('date')->where('day_of_week', $carbonDay);
                  });
            })
            ->where('is_available', true)
            ->where('start_time', $timeStr)
            ->where('end_time', '>=', $endTimeStr)
            ->orderByRaw('date IS NOT NULL DESC')
            ->first();

        if (!$schedule) {
             return response()->json(['message' => 'Mentor is not available at the requested time.'], 400);
        }

        // Check for conflicts (excluding current appointment)
        $conflict = Appointment::whereHas('mentorship', function($q) use ($mentorId) {
                $q->where('mentor_id', $mentorId);
            })
            ->where('id', '!=', $id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($appointmentStart, $appointmentEnd) {
                $q->whereBetween('scheduled_at', [$appointmentStart, $appointmentEnd])
                  ->orWhereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) BETWEEN ? AND ?', [$appointmentStart, $appointmentEnd])
                  ->orWhere(function ($sub) use ($appointmentStart, $appointmentEnd) {
                      $sub->where('scheduled_at', '<=', $appointmentStart)
                          ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) >= ?', [$appointmentEnd]);
                  });
            })
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'This time slot is already booked.'], 409);
        }

        $oldDate = $appointment->scheduled_at->format('M d, Y H:i');
        
        $appointment->update([
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $duration,
            'notes' => $validated['notes'] ?? $appointment->notes,
            'status' => 'scheduled',
        ]);



        return response()->json([
            'message' => 'Appointment rescheduled successfully',
            'appointment' => $appointment->fresh()->load('mentorship'),
        ]);
    }

    public function markCompleted(Request $request, $id)
    {
        $appointment = Appointment::with(['mentorship.mentor'])->findOrFail($id);

        $user = $request->user();
        // Only Mentor can mark as completed (or admin)
        if ($appointment->mentorship->mentor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($appointment->status !== 'scheduled' && $appointment->status !== 'rescheduled') {
            return response()->json(['message' => 'Appointment is not in a valid state to be marked completed.'], 400);
        }

        // Release funds to mentor
        $mentor = $appointment->mentorship->mentor;
        $fee = $appointment->fee ?? 0;

        \Illuminate\Support\Facades\DB::transaction(function () use ($appointment, $mentor, $fee) {
            $appointment->update(['status' => 'completed']);
            
            if ($fee > 0) {
                $mentor->increment('wallet_balance', $fee);
                WalletTransaction::create([
                    'user_id' => $mentor->id,
                    'appointment_id' => $appointment->id,
                    'amount' => $fee,
                    'type' => 'credit',
                    'description' => 'Escrow release for completed appointment #' . $appointment->id,
                ]);
            }
        });

        return response()->json([
            'message' => 'Appointment marked as completed. Funds have been released to your wallet.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    public function markMissed(Request $request, $id)
    {
        $appointment = Appointment::with(['mentorship.mentor'])->findOrFail($id);

        $user = $request->user();
        if ($appointment->mentorship->mentor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($appointment->status !== 'scheduled' && $appointment->status !== 'rescheduled') {
            return response()->json(['message' => 'Appointment is not in a valid state.'], 400);
        }

        $appointment->update(['status' => 'missed']);

        return response()->json([
            'message' => 'Appointment marked as missed. Mentee must contact you to reschedule.',
            'appointment' => $appointment->fresh(),
        ]);
    }

    private function normalizeInputMeetingLink(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $trimmed)) {
            return $trimmed;
        }

        return 'https://' . $trimmed;
    }

    private function normalizeMeetingLink(?string $meetingLink, int $appointmentId): string
    {
        $normalized = $this->normalizeInputMeetingLink($meetingLink);

        if ($normalized === null) {
            return $this->buildFallbackMeetingLink($appointmentId);
        }

        $parsed = parse_url($normalized);
        $host = strtolower((string) ($parsed['host'] ?? ''));
        $path = trim((string) ($parsed['path'] ?? ''), '/');

        if ($host === 'meet.google.com') {
            $isValidMeetCode = (bool) preg_match('/^[a-z]{3}-[a-z]{4}-[a-z]{3}$/', $path);
            $isPlaceholder = in_array($path, ['future-session', 'review-session', 'past-session'], true);

            if (!$isValidMeetCode || $isPlaceholder) {
                return $this->buildFallbackMeetingLink($appointmentId);
            }
        }

        return $normalized;
    }

    private function buildFallbackMeetingLink(int $appointmentId): string
    {
        return 'https://meet.google.com/new';
    }
}
