<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function getMentorSchedule(Request $request, $mentorId)
    {
        $mentor = User::where('role', 'mentor')->findOrFail($mentorId);
        $this->purgePastSlots((int) $mentorId);
        
        $query = Schedule::where('mentor_id', $mentorId)
            ->where('is_available', true);

        // Filter by date range if provided, default to next 7 days
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->addDays(7)->format('Y-m-d'));
        
        $query->whereBetween('date', [$startDate, $endDate]);

        $bookedStartMap = Appointment::query()
            ->where(function ($query) use ($mentorId) {
                $query->where('mentor_id', $mentorId)
                    ->orWhereHas('mentorship', function ($mentorshipQuery) use ($mentorId) {
                        $mentorshipQuery->where('mentor_id', $mentorId);
                    });
            })
            ->whereIn('status', ['scheduled', 'pending_payment', 'rescheduled'])
            ->whereBetween('scheduled_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->get()
            ->map(function (Appointment $appointment) {
                $scheduled = $appointment->scheduled_at instanceof Carbon
                    ? $appointment->scheduled_at
                    : Carbon::parse($appointment->scheduled_at);

                return $scheduled->format('Y-m-d H:i');
            })
            ->flip();

        $schedules = $query->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $schedules = $schedules
            ->values()
            ->map(function (Schedule $slot) use ($bookedStartMap) {
                $date = Carbon::parse($slot->date);
                $start = Carbon::parse($slot->start_time);
                $end = Carbon::parse($slot->end_time);

                $slotKey = $date->format('Y-m-d') . ' ' . $start->format('H:i');
                $isBooked = $bookedStartMap->has($slotKey);

                return [
                    'id' => $slot->id,
                    'mentor_id' => $slot->mentor_id,
                    'date' => $date->format('Y-m-d'),
                    'start_time' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                    'date_display' => $date->format('D, M d Y'),
                    'start_time_display' => $start->format('h:i A'),
                    'end_time_display' => $end->format('h:i A'),
                    'slot_label' => $date->format('D, M d Y') . ' ' . $start->format('h:i A'),
                    'fee' => $slot->fee,
                    'is_available' => (bool) $slot->is_available,
                    'is_booked' => $isBooked,
                ];
            });

        // Group schedules by date for easier frontend consumption
        $groupedSchedules = $schedules->groupBy(function($schedule) {
            return $schedule['date'];
        });

        return response()->json([
            'mentor' => $mentor->load('mentorProfile'),
            'schedules' => $schedules,
            'grouped_schedules' => $groupedSchedules,
            'total_slots' => $schedules->count(),
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user()->isMentor()) {
            return response()->json(['message' => 'Only mentors can create schedules'], 403);
        }

        $this->purgePastSlots((int) $request->user()->id);

        $normalizedStart = $this->normalizeTimeInput((string) $request->input('start_time', ''));
        $normalizedEnd = $this->normalizeTimeInput((string) $request->input('end_time', ''));

        if (!$normalizedStart || !$normalizedEnd) {
            return response()->json(['message' => 'Invalid time format. Use HH:mm or hh:mm AM/PM.'], 422);
        }

        $request->merge([
            'start_time' => $normalizedStart,
            'end_time' => $normalizedEnd,
        ]);

        $validated = $request->validate([
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_available' => 'sometimes|boolean',
            'fee' => 'required|numeric|min:0|max:9999.99',
        ]);

        $slotStart = Carbon::parse($validated['date'] . ' ' . $validated['start_time'], config('app.timezone'));
        if ($slotStart->lte(now(config('app.timezone')))) {
            return response()->json(['message' => 'Slot start time must be in the future'], 422);
        }

        $slotDate = Carbon::parse($validated['date']);

        // Check for overlapping schedules
        $overlapping = Schedule::where('mentor_id', $request->user()->id)
            ->where('date', $validated['date'])
            // Overlap check: existing.start < new.end AND existing.end > new.start
            // This allows adjacent slots like 09:00-10:00 and 10:00-11:00.
            ->where('start_time', '<', $validated['end_time'])
            ->where('end_time', '>', $validated['start_time'])
            ->exists();

        if ($overlapping) {
            return response()->json(['message' => 'This time slot overlaps with an existing schedule'], 400);
        }

        $schedule = Schedule::create([
            'mentor_id' => $request->user()->id,
            'date' => $validated['date'],
            'day_of_week' => $slotDate->dayOfWeek,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'fee' => $validated['fee'],
            'is_available' => $validated['is_available'] ?? true,
        ]);

        return response()->json([
            'message' => 'Schedule created successfully',
            'schedule' => $schedule,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $schedule = Schedule::findOrFail($id);

        // Authorization
        if ($schedule->mentor_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($request->has('start_time')) {
            $normalizedStart = $this->normalizeTimeInput((string) $request->input('start_time', ''));
            if (!$normalizedStart) {
                return response()->json(['message' => 'Invalid start time format.'], 422);
            }
            $request->merge(['start_time' => $normalizedStart]);
        }

        if ($request->has('end_time')) {
            $normalizedEnd = $this->normalizeTimeInput((string) $request->input('end_time', ''));
            if (!$normalizedEnd) {
                return response()->json(['message' => 'Invalid end time format.'], 422);
            }
            $request->merge(['end_time' => $normalizedEnd]);
        }

        $validated = $request->validate([
            'date' => 'sometimes|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'is_available' => 'sometimes|boolean',
            'fee' => 'sometimes|numeric|min:0|max:9999.99',
        ]);

        if (isset($validated['date'])) {
            $validated['day_of_week'] = Carbon::parse($validated['date'])->dayOfWeek;
        }

        $schedule->update($validated);

        return response()->json([
            'message' => 'Schedule updated successfully',
            'schedule' => $schedule,
        ]);
    }

    public function destroy($id)
    {
        $schedule = Schedule::findOrFail($id);

        // Authorization
        if ($schedule->mentor_id !== auth()->user()->id && auth()->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $schedule->delete();

        return response()->json([
            'message' => 'Schedule deleted successfully',
        ]);
    }
    
    public function mySchedule(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isMentor()) {
            return response()->json(['message' => 'Only mentors have schedules'], 403);
        }

        $this->purgePastSlots((int) $user->id);
        
        $query = Schedule::where('mentor_id', $user->id);

        // Filter by date range if provided
        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('date', [$request->start_date, $request->end_date])
                  ->orWhereNull('date'); // Include recurring weekly schedules
        }

        $schedules = $query->orderByRaw('date IS NULL DESC, date DESC')
            ->orderBy('start_time', 'desc')
            ->get();

        // Format dates consistently
        $schedules->transform(function ($schedule) {
            if ($schedule->date) {
                $schedule->date = \Carbon\Carbon::parse($schedule->date)->format('Y-m-d');
            }
            $schedule->start_time = \Carbon\Carbon::parse($schedule->start_time)->format('H:i');
            $schedule->end_time = \Carbon\Carbon::parse($schedule->end_time)->format('H:i');
            return $schedule;
        });

        return response()->json([
            'schedules' => $schedules,
        ]);
    }

    private function normalizeTimeInput(string $input): ?string
    {
        $value = trim($input);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function purgePastSlots(?int $mentorId = null): void
    {
        $now = now(config('app.timezone'));
        $today = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        Schedule::query()
            ->when($mentorId, function ($query) use ($mentorId) {
                $query->where('mentor_id', $mentorId);
            })
            ->whereNotNull('date')
            ->where(function ($query) use ($today, $currentTime) {
                $query->where('date', '<', $today)
                    ->orWhere(function ($sameDay) use ($today, $currentTime) {
                        $sameDay->where('date', $today)
                            ->where('end_time', '<=', $currentTime);
                    });
            })
            ->delete();
    }
}
