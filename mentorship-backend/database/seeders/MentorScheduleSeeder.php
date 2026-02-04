<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Schedule;
use Carbon\Carbon;

class MentorScheduleSeeder extends Seeder
{
    public function run()
    {
        // Get all mentors
        $mentors = User::where('role', 'mentor')->get();

        if ($mentors->isEmpty()) {
            $this->command->info('No mentors found. Skipping schedule creation.');
            return;
        }

        $endDate = Carbon::parse('2026-02-28');
        $today = Carbon::today();

        foreach ($mentors as $mentor) {
            $this->command->info("Creating schedules for mentor: {$mentor->name}");
            
            // Clear existing schedules
            Schedule::where('mentor_id', $mentor->id)->delete();

            // Create schedules from today until Feb 28, 2026
            $currentDate = $today->copy();
            
            while ($currentDate <= $endDate) {
                // Skip Sundays (day 0)
                if ($currentDate->dayOfWeek != 0) {
                    // Morning slots: 9 AM - 12 PM
                    $this->createTimeSlots($mentor->id, $currentDate, 9, 12);
                    
                    // Afternoon slots: 2 PM - 6 PM
                    $this->createTimeSlots($mentor->id, $currentDate, 14, 18);
                }
                
                $currentDate->addDay();
            }
        }

        $this->command->info('Mentor schedules created successfully!');
    }

    private function createTimeSlots($mentorId, $date, $startHour, $endHour)
    {
        // Create 1-hour slots
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $startTime = $date->copy()->setTime($hour, 0, 0);
            $endTime = $date->copy()->setTime($hour + 1, 0, 0);

            Schedule::create([
                'mentor_id' => $mentorId,
                'day_of_week' => $date->dayOfWeek,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'is_available' => true,
                'date' => $date->format('Y-m-d'),
            ]);
        }
    }
}
