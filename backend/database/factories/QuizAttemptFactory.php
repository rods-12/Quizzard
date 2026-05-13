<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuizAttemptFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'quiz_id'          => Quiz::factory(),
            'student_id'       => User::factory()->student(),
            'score'            => 0,
            'total_points'     => 0,
            'status'           => 'reviewed',
            'started_at'       => $startedAt,
            'completed_at'     => fake()->dateTimeBetween($startedAt, 'now'),
            'reviewed_at'      => now(),
            'reviewed_by'      => null,
            'teacher_feedback' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn(array $a) => [
            'status'           => 'submitted',
            'reviewed_at'      => null,
            'teacher_feedback' => null,
        ]);
    }

    public function underReview(): static
    {
        return $this->state(fn(array $a) => [
            'status'           => 'under_review',
            'reviewed_at'      => null,
            'teacher_feedback' => null,
        ]);
    }

    public function reviewed(): static
    {
        return $this->state(fn(array $a) => [
            'status'           => 'reviewed',
            'reviewed_at'      => now(),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn(array $a) => [
            'status'           => 'in_progress',
            'completed_at'     => null,
            'reviewed_at'      => null,
            'teacher_feedback' => null,
        ]);
    }
}
