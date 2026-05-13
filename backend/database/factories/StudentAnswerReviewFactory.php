<?php

namespace Database\Factories;

use App\Models\StudentAnswer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentAnswerReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_answer_id' => StudentAnswer::factory(),
            'teacher_id'        => User::factory()->teacher(),
            'points_awarded'    => fake()->randomFloat(2, 0, 5),
            'feedback'          => fake()->sentence(),
            'reviewed_at'       => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }

    public function withFeedback(string $feedback): static
    {
        return $this->state(fn(array $a) => ['feedback' => $feedback]);
    }

    public function noFeedback(): static
    {
        return $this->state(fn(array $a) => ['feedback' => null]);
    }
}
