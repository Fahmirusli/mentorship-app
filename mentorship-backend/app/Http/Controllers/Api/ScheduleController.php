<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function getMentorSchedule(Request $request, $mentorId)
    {
        $mentor = User::where('role', 'mentor')->findOrFail($mentorId);
        
        $query = Schedule::where('mentor_id', $mentorId)
            ->where('is_available', true);

        // Filter by date range if provided, default to next 7 days
        $startDate = $request->input('start_date', now()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->addDays(7)->format('Y-m-d'));
        
        $query->whereBetween('date', [$startDate, $endDate]);

        $schedules = $query->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Group schedules by date for easier frontend consumption
        $groupedSchedules = $schedules->groupBy(function($schedule) {
            return \Carbon\Carbon::parse($schedule->date)->format('Y-m-d');
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

        $validated = $request->validate([
            'date' => 'nullable|date|required_without:day_of_week',
            'day_of_week' => 'nullable|integer|between(0,6)|required_without:date', // 0=Sunday, 6=Saturday
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'is_available' => 'sometimes|boolean',
        ]);

        // Check for overlapping schedules
        $overlapping = Schedule::where('mentor_id', $request->user()->id)
            ->where(function ($q) use ($validated) {
                if (isset($validated['date'])) {
                    $q->where('date', $validated['date']);
                } else {
                    $q->where('day_of_week', $validated['day_of_week']);
                }
            })
            ->where(function ($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                      ->orWhere(function ($q) use ($validated) {
                          $q->where('start_time', '<=', $validated['start_time'])
                            ->where('end_time', '>=', $validated['end_time']);
                      });
            })
            ->exists();

        if ($overlapping) {
            return response()->json(['message' => 'This time slot overlaps with an existing schedule'], 400);
        }

        $schedule = Schedule::create([
            'mentor_id' => $request->user()->id,
            ...$validated,
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

        $validated = $request->validate([
            'day_of_week' => 'sometimes|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i|after:start_time',
            'is_available' => 'sometimes|boolean',
        ]);

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
        if ($schedule->mentor_id !== auth()->user()->id && !auth()->user()->isAdmin()) {
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
        
        $query = Schedule::where('mentor_id', $user->id);

        // Filter by date range if provided
        if ($request->has(['start_date', 'end_date'])) {
            $query->whereBetween('date', [$request->start_date, $request->end_date])
                  ->orWhereNull('date'); // Include recurring weekly schedules
        }

        $schedules = $query->orderByRaw('date IS NULL DESC, date ASC')
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        // Format dates consistently
        $schedules->transform(function ($schedule) {
            if ($schedule->date) {
                $schedule->date = \Carbon\Carbon::parse($schedule->date)->format('Y-m-d');
            }
            return $schedule;
        });

        return response()->json([
            'schedules' => $schedules,
        ]);
    }
}
