<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Resource;
use App\Models\User;

class ResourceSeeder extends Seeder
{
    public function run(): void
    {
        $mentors = User::where('role', 'mentor')->get();

        foreach ($mentors as $mentor) {
            Resource::create([
                'mentor_id' => $mentor->id,
                'title' => 'Introduction to System Design',
                'description' => 'A comprehensive guide to building scalable systems.',
                'url' => 'https://example.com/resources/system-design.pdf',
                'type' => 'document',
                'downloads_count' => rand(10, 100),
            ]);

            Resource::create([
                'mentor_id' => $mentor->id,
                'title' => 'Interview Prep Checklist',
                'description' => 'Key topics to cover before your next technical interview.',
                'url' => 'https://example.com/resources/interview-prep.pdf',
                'type' => 'document',
                'downloads_count' => rand(50, 200),
            ]);
            
            Resource::create([
                'mentor_id' => $mentor->id,
                'title' => 'My Selected Tech Talks',
                'description' => 'A playlist of must-watch tech talks.',
                'url' => 'https://youtube.com/playlist?list=xyz',
                'type' => 'video',
                'downloads_count' => rand(5, 50),
            ]);
        }
    }
}
