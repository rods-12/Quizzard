<?php

namespace Database\Factories;

use App\Models\Quiz;
use Illuminate\Database\Eloquent\Factories\Factory;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id'       => Quiz::factory(),
            'question_type' => fake()->randomElement(['multiple_choice', 'true_false', 'identification', 'matching']),
            'question_text' => fake()->sentence() . '?',
            'media_path'    => null,
            'media_type'    => null,
            'image_path'    => null,
            'video_path'    => null,
            'audio_path'    => null,
            'points'        => fake()->numberBetween(1, 5),
            'order'         => 1,
        ];
    }

    public function multipleChoice(): static
    {
        return $this->state(fn(array $a) => ['question_type' => 'multiple_choice']);
    }

    public function trueFalse(): static
    {
        return $this->state(fn(array $a) => ['question_type' => 'true_false']);
    }

    public function identification(): static
    {
        return $this->state(fn(array $a) => ['question_type' => 'identification']);
    }

    public function matching(): static
    {
        return $this->state(fn(array $a) => ['question_type' => 'matching']);
    }
}
