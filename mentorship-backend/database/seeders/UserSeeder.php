<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'phone' => '0123456789',
            'bio' => 'System Administrator',
            'is_active' => true,
        ]);

        // Mentor Users
        $mentors = [
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentor',
                'phone' => '0123456780',
                'bio' => 'Senior Full Stack Developer with 8 years of experience in React, Node.js, and cloud technologies.',
                'skills' => ['React', 'Node.js', 'AWS', 'MongoDB', 'Docker'],
                'interests' => ['Web Development', 'Cloud Computing', 'Mentoring'],
                'profile_image' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Michael Chen',
                'email' => 'michael@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentor',
                'phone' => '0123456781',
                'bio' => 'Data Scientist specializing in Machine Learning and AI with 6 years of experience.',
                'skills' => ['Python', 'TensorFlow', 'Scikit-learn', 'Data Analysis', 'SQL'],
                'interests' => ['Machine Learning', 'Data Science', 'AI'],
                'profile_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Emily Rodriguez',
                'email' => 'emily@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentor',
                'phone' => '0123456782',
                'bio' => 'Mobile App Developer with expertise in React Native and Flutter.',
                'skills' => ['React Native', 'Flutter', 'iOS', 'Android', 'Firebase'],
                'interests' => ['Mobile Development', 'UI/UX', 'App Design'],
                'profile_image' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'David Kumar',
                'email' => 'david@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentor',
                'phone' => '0123456783',
                'bio' => 'DevOps Engineer with 7 years experience in CI/CD, Kubernetes, and cloud infrastructure.',
                'skills' => ['Docker', 'Kubernetes', 'Jenkins', 'AWS', 'Terraform'],
                'interests' => ['DevOps', 'Cloud Infrastructure', 'Automation'],
                'profile_image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                'is_active' => true,
            ],
            [
                'name' => 'Lisa Wong',
                'email' => 'lisa@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentor',
                'phone' => '0123456784',
                'bio' => 'UI/UX Designer with a passion for creating beautiful and functional interfaces.',
                'skills' => ['Figma', 'Adobe XD', 'UI Design', 'UX Research', 'Prototyping'],
                'interests' => ['Design', 'User Experience', 'Product Design'],
                'profile_image' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
                'is_active' => true,
            ],
        ];

        foreach ($mentors as $mentor) {
            User::create($mentor);
        }

        // Mentee Users
        $mentees = [
            [
                'name' => 'Ahmad Fahmi',
                'email' => 'fahmi@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentee',
                'phone' => '0123456790',
                'bio' => 'Computer Science student looking to learn web development.',
                'skills' => ['HTML', 'CSS', 'JavaScript', 'PHP'],
                'interests' => ['Web Development', 'Backend Development'],
                'is_active' => true,
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentee',
                'phone' => '0123456791',
                'bio' => 'Fresh graduate interested in data science and analytics.',
                'skills' => ['Python', 'Excel', 'SQL'],
                'interests' => ['Data Science', 'Analytics', 'Machine Learning'],
                'is_active' => true,
            ],
            [
                'name' => 'Wei Lun',
                'email' => 'weilun@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentee',
                'phone' => '0123456792',
                'bio' => 'Aspiring mobile app developer learning React Native.',
                'skills' => ['JavaScript', 'React', 'HTML', 'CSS'],
                'interests' => ['Mobile Development', 'App Development'],
                'is_active' => true,
            ],
            [
                'name' => 'Priya Devi',
                'email' => 'priya@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentee',
                'phone' => '0123456793',
                'bio' => 'UI/UX design enthusiast looking to break into the industry.',
                'skills' => ['Figma', 'Photoshop', 'Basic HTML/CSS'],
                'interests' => ['UI Design', 'UX Design', 'Graphic Design'],
                'is_active' => true,
            ],
            [
                'name' => 'Fahmi',
                'email' => 'arefahmi1@gmail.com',
                'password' => Hash::make('password123'),
                'role' => 'mentee',
                'phone' => '0123456794',
                'bio' => 'Passionate learner interested in full-stack development.',
                'skills' => ['HTML', 'CSS', 'JavaScript'],
                'interests' => ['Web Development', 'Software Engineering'],
                'is_active' => true,
            ],
        ];

        foreach ($mentees as $mentee) {
            User::create($mentee);
        }
    }
}
