<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'superadmin@quizzard.com'],
            [
                'name'     => 'Super Admin',
                'email'    => 'superadmin@quizzard.com',
                'password' => Hash::make('SuperAdmin@1234'),
                'role'     => 'superadmin',
                'status'   => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@quizzard.com'],
            [
                'name'     => 'Admin',
                'email'    => 'admin@quizzard.com',
                'password' => Hash::make('Admin@1234'),
                'role'     => 'admin',
                'status'   => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'teacher@quizzard.com'],
            [
                'name'     => 'Teacher Demo',
                'email'    => 'teacher@quizzard.com',
                'password' => Hash::make('Teacher@1234'),
                'role'     => 'teacher',
                'status'   => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'student@quizzard.com'],
            [
                'name'     => 'Student Demo',
                'email'    => 'student@quizzard.com',
                'password' => Hash::make('Student@1234'),
                'role'     => 'student',
                'status'   => 'active',
            ]
        );
    }
}
