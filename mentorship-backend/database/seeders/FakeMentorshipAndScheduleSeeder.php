<?php

namespace Database\Seeders;

use App\Models\Mentorship;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FakeMentorshipAndScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $mentors = User::where('role', 'mentor')->where('is_active', true)->get();
        $mentees = User::where('role', 'mentee')->where('is_active', true)->get();

        if ($mentors->isEmpty() || $mentees->isEmpty()) {
            $this->command?->warn('No active mentors or mentees found. Skipping fake mentorship/slot seeding.');
            return;
        }

        foreach ($mentors as $mentor) {
            // Create/refresh upcoming availability for the next 14 weekdays.
            Schedule::where('mentor_id', $mentor->id)
                ->whereDate('date', '>=', Carbon::today())
                ->delete();

            $hourlyRate = $mentor->mentorProfile->hourly_rate ?? 50;

            for ($d = 0; $d < 14; $d++) {
                $date = Carbon::today()->addDays($d);
                if ($date->isWeekend()) {
                    continue;
                }

                $slots = [
                    ['09:00:00', '10:00:00'],
                    ['10:00:00', '11:00:00'],
                    ['14:00:00', '15:00:00'],
                    ['15:00:00', '16:00:00'],
                ];

                foreach ($slots as [$start, $end]) {
                    Schedule::create([
                        'mentor_id' => $mentor->id,
                        'date' => $date->format('Y-m-d'),
                        'day_of_week' => $date->dayOfWeek,
                        'start_time' => $start,
                        'end_time' => $end,
                        'is_available' => true,
                        'fee' => $hourlyRate,
                        'total_slots' => 1,
                        'booked_slots' => 0,
                    ]);
                }
            }
        }

        // Seed mentor-mentee relationships if missing.
        foreach ($mentees as $idx => $mentee) {
            $mentor = $mentors[$idx % $mentors->count()];

            Mentorship::firstOrCreate(
                [
                    'mentor_id' => $mentor->id,
                    'mentee_id' => $mentee->id,
                ],
                [
                    'status' => 'active',
                    'goals' => 'Seeded mentorship for demo availability and booking.',
                    'start_date' => Carbon::today(),
                ]
            );
        }

        $this->command?->info('Fake mentorships and mentor availability seeded successfully.');
    }
}
