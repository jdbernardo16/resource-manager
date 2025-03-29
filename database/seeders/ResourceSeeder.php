<?php

namespace Database\Seeders;

use App\Models\Resource; // Import Resource model
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Resource::updateOrCreate(
            ['email' => 'john.doe@example.com'],
            [
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'skills' => 'PHP, Laravel, Vue.js, MySQL',
            ]
        );

        Resource::updateOrCreate(
            ['email' => 'jane.smith@example.com'],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'skills' => 'React, Node.js, MongoDB, Tailwind CSS',
            ]
        );

        Resource::updateOrCreate(
            ['email' => 'peter.jones@example.com'],
            [
                'name' => 'Peter Jones',
                'email' => 'peter.jones@example.com',
                'skills' => 'Python, Django, PostgreSQL, Docker',
            ]
        );

         Resource::updateOrCreate(
            ['email' => 'alice.wonder@example.com'],
            [
                'name' => 'Alice Wonder',
                'email' => 'alice.wonder@example.com',
                'skills' => 'UI/UX Design, Figma, CSS',
            ]
        );
    }
}
