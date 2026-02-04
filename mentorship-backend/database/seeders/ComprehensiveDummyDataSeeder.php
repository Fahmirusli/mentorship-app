<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Mentorship;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\MentorProfile;
use App\Models\MenteeProfile;
use Carbon\Carbon;

class ComprehensiveDummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all mentors and mentees
        $mentors = User::where('role', 'mentor')->get();
        $mentees = User::where('role', 'mentee')->get();

        if ($mentors->isEmpty() || $mentees->isEmpty()) {
            $this->command->info('No mentors or mentees found. Run UserSeeder first.');
            return;
        }

        $this->command->info("Found " . $mentors->count() . " mentors and " . $mentees->count() . " mentees");

        // Ensure all mentees have at least one active mentorship
        foreach ($mentees as $index => $mentee) {
            $mentor = $mentors[$index % $mentors->count()];
            
            // Check if mentorship already exists
            $exists = Mentorship::where('mentor_id', $mentor->id)
                ->where('mentee_id', $mentee->id)
                ->exists();

            if (!$exists) {
                $mentorship = Mentorship::create([
                    'mentor_id' => $mentor->id,
                    'mentee_id' => $mentee->id,
                    'status' => 'active',
                    'goals' => $this->generateGoal($mentee),
                    'start_date' => Carbon::now()->subDays(rand(1, 60)),
                    'end_date' => Carbon::now()->addMonths(rand(3, 6)),
                ]);

                $this->command->info("✓ Created mentorship: {$mentor->name} → {$mentee->name}");

                // Create appointments for this mentorship
                $this->createAppointmentsForMentorship($mentorship);

                // Create schedules for mentor
                $this->createSchedulesForMentor($mentor);
            }
        }

        // Create some additional cross-mentorships (one mentor with multiple mentees)
        foreach ($mentors->take(min(3, $mentors->count())) as $mentor) {
            $assignedMentees = $mentees->where('id', '!=', $mentor->id)->take(2);
            
            foreach ($assignedMentees as $mentee) {
                $exists = Mentorship::where('mentor_id', $mentor->id)
                    ->where('mentee_id', $mentee->id)
                    ->exists();

                if (!$exists && rand(0, 1)) {
                    $mentorship = Mentorship::create([
                        'mentor_id' => $mentor->id,
                        'mentee_id' => $mentee->id,
                        'status' => 'pending',
                        'goals' => $this->generateGoal($mentee),
                    ]);

                    $this->command->info("✓ Created pending mentorship: {$mentor->name} ↔ {$mentee->name}");
                }
            }
        }

        $this->command->info("✓ All dummy data created successfully!");
    }

    /**
     * Create appointments for a mentorship
     */
    private function createAppointmentsForMentorship(Mentorship $mentorship): void
    {
        // Create 3-5 past completed appointments
        for ($i = 0; $i < rand(3, 5); $i++) {
            Appointment::create([
                'mentorship_id' => $mentorship->id,
                'scheduled_at' => Carbon::now()->subDays(rand(5, 30))->setHour(rand(9, 17))->setMinute(0),
                'duration_minutes' => 60,
                'status' => 'completed',
                'meeting_link' => 'https://meet.google.com/' . $this->generateMeetingId(),
                'notes' => $this->getRandomNotes(),
                'fee' => 50.00,
            ]);
        }

        // Create 2-4 future scheduled appointments
        for ($i = 0; $i < rand(2, 4); $i++) {
            Appointment::create([
                'mentorship_id' => $mentorship->id,
                'scheduled_at' => Carbon::now()->addDays(rand(1, 21))->setHour(rand(9, 17))->setMinute(0),
                'duration_minutes' => 60,
                'status' => 'scheduled',
                'meeting_link' => 'https://meet.google.com/' . $this->generateMeetingId(),
                'notes' => $this->getRandomUpcomingNotes(),
                'fee' => 50.00,
            ]);
        }
    }

    /**
     * Create availability schedules for mentor
     */
    private function createSchedulesForMentor(User $mentor): void
    {
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        
        foreach ($daysOfWeek as $day) {
            // Check if schedule already exists
            $exists = Schedule::where('mentor_id', $mentor->id)
                ->where('day_of_week', $day)
                ->exists();

            if (!$exists) {
                Schedule::create([
                    'mentor_id' => $mentor->id,
                    'day_of_week' => $day,
                    'start_time' => '09:00',
                    'end_time' => '17:00',
                    'is_available' => true,
                ]);
            }
        }
    }

    /**
     * Generate random mentorship goals
     */
    private function generateGoal(User $mentee): string
    {
        $goals = [
            'Learn full-stack web development and build a portfolio',
            'Master React and modern JavaScript frameworks',
            'Become proficient in data science and machine learning',
            'Develop mobile app development skills',
            'Learn cloud technologies and DevOps practices',
            'Improve coding skills and best practices',
            'Transition into tech industry from non-tech background',
            'Prepare for senior developer position',
            'Learn about system design and architecture',
            'Build confidence in technical interviews',
        ];

        return $goals[array_rand($goals)];
    }

    /**
     * Get random past appointment notes
     */
    private function getRandomNotes(): string
    {
        $notes = [
            'Discussed career goals and technical progress. Mentee showed strong understanding of React concepts.',
            'Reviewed code quality and best practices. Provided feedback on the project structure.',
            'Discussed database optimization and query performance.',
            'Reviewed resume and interview preparation strategies.',
            'Discussed work-life balance and professional growth.',
            'Code review session - covered error handling and testing practices.',
            'Discussed deployment strategies and CI/CD pipelines.',
        ];

        return $notes[array_rand($notes)];
    }

    /**
     * Get random upcoming appointment notes
     */
    private function getRandomUpcomingNotes(): string
    {
        $notes = [
            'Session to review ongoing projects and discuss next steps.',
            'Technical interview preparation and mock interview practice.',
            'Discussion on advanced React patterns and optimization techniques.',
            'Review of portfolio projects and career development.',
            'Deep dive into system design and architecture patterns.',
            'Code review and refactoring session for recent work.',
        ];

        return $notes[array_rand($notes)];
    }

    /**
     * Generate random meeting ID
     */
    private function generateMeetingId(): string
    {
        return implode('-', [
            substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 3),
            substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 4),
            substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 3),
        ]);
    }
}
