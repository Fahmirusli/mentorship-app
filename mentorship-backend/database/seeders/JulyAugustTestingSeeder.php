<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Schedule;
use App\Models\Course;
use Carbon\Carbon;

class JulyAugustTestingSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Starting to generate July-August testing data...');

        // 1. Get or create a mentor
        $mentor = User::where('name', 'John Mentor')->where('role', 'mentor')->first();
        if (!$mentor) {
            $mentor = User::create([
                'name' => 'John Mentor',
                'email' => 'johnmentor@example.com',
                'password' => bcrypt('password'),
                'role' => 'mentor',
                'points' => 0
            ]);
            $this->command->info("Created a new Mentor: John Mentor (ID: {$mentor->id}).");
        }

        // 2. Create schedules from today to August 31, 2026
        $startDate = Carbon::today();
        $endDate = Carbon::parse('2026-08-31');

        $this->command->info("Generating schedules for Mentor '{$mentor->name}' from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");
        
        // Only delete existing slots in this range so we don't destroy past data
        Schedule::where('mentor_id', $mentor->id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->delete();

        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            // Skip Sundays (day 0) and Saturdays (day 6) to make it realistic
            if ($currentDate->dayOfWeek != 0 && $currentDate->dayOfWeek != 6) {
                // Morning slots: 10 AM - 12 PM
                $this->createTimeSlots($mentor->id, $currentDate, 10, 12);
                // Afternoon slots: 2 PM - 4 PM
                $this->createTimeSlots($mentor->id, $currentDate, 14, 16);
            }
            $currentDate->addDay();
        }

        // 3. Create dummy courses if the mentor doesn't have enough
        if (Course::where('mentor_id', $mentor->id)->count() < 3) {
            $this->command->info('Creating 3 dummy courses...');
            
            Course::create([
                'mentor_id' => $mentor->id,
                'title' => 'Web Development Bootcamp (July Edition)',
                'description' => 'A complete guide to full-stack web development starting this July. Learn React, Node, and Laravel.',
                'price' => 199.99,
                'tags' => ['Web Dev', 'Fullstack', 'Programming'],
                'syllabus' => [
                    ['week' => 1, 'topic' => 'HTML/CSS Basics'],
                    ['week' => 2, 'topic' => 'JavaScript Deep Dive'],
                    ['week' => 3, 'topic' => 'Backend with Laravel']
                ],
            ]);

            Course::create([
                'mentor_id' => $mentor->id,
                'title' => 'Advanced UI/UX Design',
                'description' => 'Master Figma and user experience principles. Great for beginners and pros.',
                'price' => 99.00,
                'tags' => ['Design', 'UI/UX', 'Figma'],
                'syllabus' => [
                    ['week' => 1, 'topic' => 'Intro to Figma'],
                    ['week' => 2, 'topic' => 'Wireframing & Prototyping']
                ],
            ]);

            Course::create([
                'mentor_id' => $mentor->id,
                'title' => 'Career Mentorship & Interview Prep',
                'description' => 'Prepare for technical interviews in August. Resume reviews, mock interviews, and more.',
                'price' => 149.50,
                'tags' => ['Career', 'Interview', 'Soft Skills'],
                'syllabus' => [
                    ['week' => 1, 'topic' => 'Resume Building'],
                    ['week' => 2, 'topic' => 'Mock Interviews']
                ],
            ]);
        }

        $this->command->info('July & August Testing Data successfully created!');
    }

    private function createTimeSlots($mentorId, $date, $startHour, $endHour)
    {
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
