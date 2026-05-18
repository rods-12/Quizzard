<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Core admin/demo accounts first (no dependencies)
        $this->call(AdminUserSeeder::class);

        // 2. Test classes/teachers/students (no quiz dependencies)
        $this->call(AdminClassesTestSeeder::class);

        // 3. Main demo data: teachers, students, quizzes, classes,
        //    attempts, student_answers, student_answer_reviews
        $this->call(QuizzardDemoSeeder::class);

        // 4. Announcements (depends on admin users existing)
        $this->call(AnnouncementSeeder::class);

        // Minimal test user
        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
