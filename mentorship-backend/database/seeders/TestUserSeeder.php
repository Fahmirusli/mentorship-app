<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\MentorProfile;
use App\Models\MenteeProfile;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        // 1. Create Admin
        $adminEmail = 'admin@uplifts.dev';
        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'Admin User',
                'email' => $adminEmail,
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
            $this->command->info('Admin created: ' . $adminEmail);
        } else {
            $this->command->warn('Admin already exists: ' . $adminEmail);
        }

        // 2. Create Mentor
        $mentorEmail = 'mentor@uplifts.dev';
        $mentor = User::where('email', $mentorEmail)->first();
        if (!$mentor) {
            $mentor = User::create([
                'name' => 'John Mentor',
                'email' => $mentorEmail,
                'password' => Hash::make('password123'),
                'role' => 'mentor',
                'email_verified_at' => now(),
                'is_active' => true,
                'skills' => ['JavaScript', 'React', 'Node.js', 'System Design'],
                'interests' => ['Teaching', 'Open Source'],
            ]);
            
            MentorProfile::create([
                'user_id' => $mentor->id,
                'job_title' => 'Senior Software Engineer',
                'company' => 'Tech Corp Inc.',
                'industry' => 'Technology',
                'years_of_experience' => 8,
                'expertise_areas' => ['Frontend Development', 'Backend Architecture', 'DevOps'],
                'mentorship_approach' => 'Hands-on coding and architectural reviews.',
                'is_available' => true,
                'rating' => 5.0,
            ]);
            
            $this->command->info('Mentor created: ' . $mentorEmail);
        } else {
            $this->command->warn('Mentor already exists: ' . $mentorEmail);
        }

        // 3. Create Mentee
        $menteeEmail = 'mentee@uplifts.dev';
        $mentee = User::where('email', $menteeEmail)->first();
        if (!$mentee) {
            $mentee = User::create([
                'name' => 'Jane Mentee',
                'email' => $menteeEmail,
                'password' => Hash::make('password123'),
                'role' => 'mentee',
                'email_verified_at' => now(),
                'is_active' => true,
                'skills' => ['HTML', 'CSS', 'Basic JavaScript'],
                'interests' => ['Web Development', 'Career Growth'],
            ]);
            
            MenteeProfile::create([
                'user_id' => $mentee->id,
                'education_level' => 'Undergraduate',
                'field_of_study' => 'Computer Science',
                'career_goals' => 'Become a Full Stack Developer',
                'current_skills' => ['HTML', 'CSS'],
                'skills_to_learn' => ['React', 'Laravel', 'AWS'],
            ]);

            $this->command->info('Mentee created: ' . $menteeEmail);
        } else {
            $this->command->warn('Mentee already exists: ' . $menteeEmail);
        }
    }
}
