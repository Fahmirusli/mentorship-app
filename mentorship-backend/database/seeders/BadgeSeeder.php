<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Badge::firstOrCreate(['name' => 'Getting Started'], [
            'description' => 'Complete your profile setup.',
            'icon_url' => 'https://api.dicebear.com/7.x/icons/svg?seed=Rocket',
            'required_points' => 10
        ]);

        Badge::firstOrCreate(['name' => 'First Session'], [
            'description' => 'Complete your first mentorship session.',
            'icon_url' => 'https://api.dicebear.com/7.x/icons/svg?seed=Star',
            'required_points' => 50
        ]);

        Badge::firstOrCreate(['name' => 'Active Learner'], [
            'description' => 'Reach 100 points.',
            'icon_url' => 'https://api.dicebear.com/7.x/icons/svg?seed=Book',
            'required_points' => 100
        ]);

        Badge::firstOrCreate(['name' => 'Super Mentor'], [
            'description' => 'Reach 500 points.',
            'icon_url' => 'https://api.dicebear.com/7.x/icons/svg?seed=Crown',
            'required_points' => 500
        ]);
    }
}
