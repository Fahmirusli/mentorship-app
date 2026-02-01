<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Mentorship;
use Carbon\Carbon;

class MentorshipSeeder extends Seeder
{
    public function run(): void
    {
        $mentors = User::where('role', 'mentor')->get();
        $mentees = User::where('role', 'mentee')->get();

        if ($mentors->count() > 0 && $mentees->count() > 0) {
            // Create some active mentorships
            Mentorship::create([
                'mentor_id' => $mentors[0]->id, // Sarah (Full Stack)
                'mentee_id' => $mentees[0]->id, // Ahmad
                'status' => 'active',
                'goals' => 'Learn React and Node.js to become a full stack developer.',
                'start_date' => Carbon::now()->subMonths(2),
                'end_date' => Carbon::now()->addMonths(4),
            ]);

            Mentorship::create([
                'mentor_id' => $mentors[1]->id, // Michael (Data Science)
                'mentee_id' => $mentees[1]->id, // Siti
                'status' => 'active',
                'goals' => 'Master machine learning and build a portfolio of data science projects.',
                'start_date' => Carbon::now()->subMonth(),
                'end_date' => Carbon::now()->addMonths(5),
            ]);

            Mentorship::create([
                'mentor_id' => $mentors[2]->id, // Emily (Mobile Dev)
                'mentee_id' => $mentees[2]->id, // Wei Lun
                'status' => 'active',
                'goals' => 'Build and publish a mobile app using React Native.',
                'start_date' => Carbon::now()->subWeeks(3),
                'end_date' => Carbon::now()->addMonths(6),
            ]);

            // Create a pending mentorship
            Mentorship::create([
                'mentor_id' => $mentors[4]->id, // Lisa (UI/UX)
                'mentee_id' => $mentees[3]->id, // Priya
                'status' => 'pending',
                'goals' => 'Learn professional UI/UX design and build a strong portfolio.',
                'start_date' => null,
                'end_date' => null,
            ]);
        }
    }
}
