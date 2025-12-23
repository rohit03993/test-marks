<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed subjects (Physics, Chemistry, Mathematics)
        $this->call(SubjectSeeder::class);

        // User::factory(10)->create();

        // Create admin user
        User::create([
            'name' => 'Admin',
            'email' => 'admin@testmarks.com',
            'password' => bcrypt('admin123'),
            'email_verified_at' => now(),
        ]);
    }
}
