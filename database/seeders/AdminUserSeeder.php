<?php

namespace Database\Seeders;

use App\Models\User; // Import User model
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // Import Hash facade

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@praxxys.ph'], // Find user by email
            [
                'name' => 'Admin User', // Default name
                'email' => 'admin@praxxys.ph',
                'password' => Hash::make('password'), // Hash the password
                'email_verified_at' => now(), // Mark email as verified
            ]
        );
    }
}
