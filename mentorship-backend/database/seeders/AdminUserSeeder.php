<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user if doesn't exist
        User::firstOrCreate(
            ['email' => 'admin@uplift.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'email_verified_at' => now()
            ]
        );

        echo "Admin user created successfully!\n";
        echo "Email: admin@uplift.com\n";
        echo "Password: admin123\n";
    }
}
