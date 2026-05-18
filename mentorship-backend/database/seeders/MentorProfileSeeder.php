<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MentorProfile;

class MentorProfileSeeder extends Seeder
{
    public function run(): void
    {
        $mentors = User::where('role', 'mentor')->get();

        $profiles = [
            [
                'expertise_areas' => ['React', 'Node.js', 'Full Stack Development', 'Web Development'],
                'industry' => 'Technology',
                'job_title' => 'Senior Full Stack Developer',
                'company' => 'Tech Solutions Sdn Bhd',
                'years_of_experience' => 8,
                'mentorship_approach' => 'Hands-on learning with real-world projects and code reviews.',
                'is_available' => true,
                'rating' => 4.8,
                'total_mentees' => 12,
            ],
            [
                'expertise_areas' => ['Python', 'Machine Learning', 'Data Science', 'AI'],
                'industry' => 'Data & Analytics',
                'job_title' => 'Senior Data Scientist',
                'company' => 'DataCorp Malaysia',
                'years_of_experience' => 6,
                'mentorship_approach' => 'Project-based learning with focus on real data problems.',
                'is_available' => true,
                'rating' => 4.9,
                'total_mentees' => 8,
            ],
            [
                'expertise_areas' => ['React Native', 'Flutter', 'Mobile Development', 'iOS', 'Android'],
                'industry' => 'Mobile Technology',
                'job_title' => 'Lead Mobile Developer',
                'company' => 'AppWorks Studio',
                'years_of_experience' => 5,
                'mentorship_approach' => 'Build complete apps from scratch while learning best practices.',
                'is_available' => true,
                'rating' => 4.7,
                'total_mentees' => 10,
            ],
            [
                'expertise_areas' => ['DevOps', 'Docker', 'Kubernetes', 'CI/CD', 'AWS'],
                'industry' => 'Cloud & Infrastructure',
                'job_title' => 'Senior DevOps Engineer',
                'company' => 'CloudTech Asia',
                'years_of_experience' => 7,
                'mentorship_approach' => 'Learn by doing - set up real infrastructure and automation.',
                'is_available' => true,
                'rating' => 4.6,
                'total_mentees' => 6,
            ],
            [
                'expertise_areas' => ['UI Design', 'UX Design', 'Product Design', 'User Research'],
                'industry' => 'Design',
                'job_title' => 'Senior UI/UX Designer',
                'company' => 'Design Studio KL',
                'years_of_experience' => 6,
                'mentorship_approach' => 'Portfolio building with real client projects and feedback.',
                'is_available' => true,
                'rating' => 4.9,
                'total_mentees' => 15,
            ],
        ];

        foreach ($mentors as $index => $mentor) {
            MentorProfile::create([
                'user_id' => $mentor->id,
                ...($profiles[$index] ?? $profiles[0]),
            ]);
        }
    }
}
