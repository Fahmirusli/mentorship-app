<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MenteeProfile;

class MenteeProfileSeeder extends Seeder
{
    public function run(): void
    {
        $mentees = User::where('role', 'mentee')->get();

        $profiles = [
            [
                'current_skills' => ['HTML', 'CSS', 'JavaScript', 'PHP', 'MySQL'],
                'skills_to_learn' => ['React', 'Node.js', 'MongoDB', 'REST API'],
                'career_goals' => 'Become a Full Stack Developer and work for a tech startup.',
                'education_level' => "Bachelor's Degree",
                'field_of_study' => 'Computer Science',
            ],
            [
                'current_skills' => ['Python', 'Excel', 'SQL', 'Statistics'],
                'skills_to_learn' => ['Machine Learning', 'TensorFlow', 'Data Visualization', 'Big Data'],
                'career_goals' => 'Transition into Data Science role at a leading company.',
                'education_level' => "Bachelor's Degree",
                'field_of_study' => 'Mathematics',
            ],
            [
                'current_skills' => ['JavaScript', 'React', 'HTML', 'CSS', 'Git'],
                'skills_to_learn' => ['React Native', 'Flutter', 'Mobile UI/UX', 'Firebase'],
                'career_goals' => 'Build and publish mobile apps on App Store and Play Store.',
                'education_level' => "Bachelor's Degree",
                'field_of_study' => 'Information Technology',
            ],
            [
                'current_skills' => ['Figma', 'Photoshop', 'Basic HTML/CSS', 'Wireframing'],
                'skills_to_learn' => ['Advanced Figma', 'UX Research', 'Prototyping', 'Design Systems'],
                'career_goals' => 'Become a professional UI/UX Designer at a product company.',
                'education_level' => 'Diploma',
                'field_of_study' => 'Graphic Design',
            ],
        ];

        foreach ($mentees as $index => $mentee) {
            MenteeProfile::create([
                'user_id' => $mentee->id,
                ...$profiles[$index],
            ]);
        }
    }
}