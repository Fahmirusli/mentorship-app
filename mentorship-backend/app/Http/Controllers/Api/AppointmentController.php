<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Mentorship;
use App\Services\TelegramNotificationService;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Appointment::with(['mentorship.mentor', 'mentorship.mentee']);

        // Get appointments for user's mentorships
        $mentorshipIds = Mentorship::where('mentor_id', $user->id)
            ->orWhere('mentee_id', $user->id)
            ->pluck('id');

        $query->whereIn('mentorship_id', $mentorshipIds);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('scheduled_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('scheduled_at', '<=', $request->to_date);
        }

        $appointments = $query->orderBy('scheduled_at', 'asc')->get();

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'mentorship_id' => 'required|exists:mentorships,id',
            'scheduled_at' => 'required|date|after:now',
            'duration_minutes' => 'required|integer|min:15|max:180',
            'meeting_link' => 'nullable|url',
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
            'fee' => $fee,
        ]);

        // Send Telegram notification
        try {
            $telegramService = app(TelegramNotificationService::class);
            $telegramService->notifyNewAppointment($appointment->load('mentorship.mentor', 'mentorship.mentee'));
        } catch (\Exception $e) {
            \Log::warning('Telegram notification failed: ' . $e->getMessage());
        }

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
            'meeting_link' => 'sometimes|url',
            'notes' => 'sometimes|string',
        ]);

        // Authorization
        $user = $request->user();
        if ($appointment->mentorship->mentor_id !== $user->id && 
            $appointment->mentorship->mentee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
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
        if ($appointment->mentorship->mentee_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Send Telegram notification before deletion
        try {
            $telegramService = app(TelegramNotificationService::class);
            $telegramService->notifyAppointmentCancelled($appointment);
        } catch (\Exception $e) {
            \Log::warning('Telegram notification failed: ' . $e->getMessage());
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
            ->where('start_time', '<=', $timeStr)
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

        // Send Telegram notification
        try {
            $telegramService = app(TelegramNotificationService::class);
            $telegramService->notifyAppointmentRescheduled($appointment->fresh()->load('mentorship.mentor', 'mentorship.mentee'), $oldDate);
        } catch (\Exception $e) {
            \Log::warning('Telegram notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Appointment rescheduled successfully',
            'appointment' => $appointment->fresh()->load('mentorship'),
        ]);
    }
