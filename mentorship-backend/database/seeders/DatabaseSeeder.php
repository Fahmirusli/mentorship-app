<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AdminUserSeeder::class,        // Add this
            TestUserSeeder::class,         // Add this
            MentorProfileSeeder::class,
            MenteeProfileSeeder::class,
            JobSeeder::class,
            MentorScheduleSeeder::class,   // Add this
            TestScheduleSeeder::class,     // Add this
            MentorshipSeeder::class,
            AppointmentSeeder::class,
            ResourceSeeder::class,
            FeedbackSeeder::class,
            ComprehensiveDummyDataSeeder::class,
        ]);
    }
}
