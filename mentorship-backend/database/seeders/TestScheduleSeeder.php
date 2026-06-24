<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Schedule;
use Carbon\Carbon;

class TestScheduleSeeder extends Seeder
{
    public function run()
    {
        $mentorEmail = 'mentor@uplifts.dev';
        $mentor = User::where('email', $mentorEmail)->first();

        if (!$mentor) {
            $this->command->error("Mentor with email {$mentorEmail} not found. Please run TestUserSeeder first.");
            return;
        }

        // Create schedules for next 3 days
        for ($i = 0; $i < 3; $i++) {
            $date = Carbon::now()->addDays($i);
            
            // Morning Slot: 9 AM - 12 PM
            Schedule::create([
                'mentor_id' => $mentor->id,
                'date' => $date->format('Y-m-d'),
                'day_of_week' => $date->dayOfWeek,
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
                'is_available' => true,
            ]);

            // Afternoon Slot: 2 PM - 5 PM
            Schedule::create([
                'mentor_id' => $mentor->id,
                'date' => $date->format('Y-m-d'),
                'day_of_week' => $date->dayOfWeek,
                'start_time' => '14:00:00',
                'end_time' => '17:00:00',
                'is_available' => true,
            ]);
            
            $this->command->info("Created schedule for " . $date->format('Y-m-d'));
        }
    }
}
