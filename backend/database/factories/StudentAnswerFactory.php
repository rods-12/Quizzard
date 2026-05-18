<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuizAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentAnswerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'attempt_id'    => QuizAttempt::factory(),
            'question_id'   => Question::factory(),
            'answer_given'  => fake()->sentence(),
            'is_correct'    => fake()->boolean(60),
            'points_earned' => fake()->numberBetween(0, 5),
            'justification' => null,
        ];
    }

    public function correct(): static
    {
        return $this->state(fn(array $a) => [
            'is_correct'    => true,
            'points_earned' => $a['points_earned'] ?? 1,
        ]);
    }

    public function incorrect(): static
    {
        return $this->state(fn(array $a) => [
            'is_correct'    => false,
            'points_earned' => 0,
        ]);
    }

    public function withJustification(): static
    {
        return $this->state(fn(array $a) => [
            'justification' => fake()->sentence(),
        ]);
    }
}
