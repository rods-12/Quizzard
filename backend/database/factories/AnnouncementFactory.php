<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnouncementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'admin_id' => User::factory()->admin(),
            'title'    => fake()->sentence(6),
            'body'     => fake()->paragraphs(2, true),
        ];
    }

    public function forAdmin(int $adminId): static
    {
        return $this->state(fn(array $a) => ['admin_id' => $adminId]);
    }
}
