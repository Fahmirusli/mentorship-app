<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Appointment;
use App\Models\Mentorship;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    public function run(): void
    {
        $mentorships = Mentorship::where('status', 'active')->get();

        foreach ($mentorships as $mentorship) {
            // Past appointments (completed)
            for ($i = 0; $i < 3; $i++) {
                Appointment::create([
                    'mentorship_id' => $mentorship->id,
                    'scheduled_at' => Carbon::now()->subDays(rand(1, 30))->setHour(rand(9, 17))->setMinute(0),
                    'duration_minutes' => 60,
                    'status' => 'completed',
                    'meeting_link' => 'https://meet.google.com/abc-defg-hij',
                    'notes' => 'Discussed career goals and progress.',
                    'fee' => 50.00,
                ]);
            }

            // Future appointments (scheduled)
            for ($i = 0; $i < 2; $i++) {
                Appointment::create([
                    'mentorship_id' => $mentorship->id,
                    'scheduled_at' => Carbon::now()->addDays(rand(1, 14))->setHour(rand(9, 17))->setMinute(0),
                    'duration_minutes' => 60,
                    'status' => 'scheduled',
                    'meeting_link' => 'https://meet.google.com/xyz-uvw-rst',
                    'notes' => 'Upcoming session to review project code.',
                    'fee' => 50.00,
                ]);
            }
        }
    }
}
