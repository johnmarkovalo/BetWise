<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'role' => fake()->randomElement(TeamRole::cases()),
            'status' => TeamStatus::Active,
        ];
    }

    public function primary(): static
    {
        return $this->state(['role' => TeamRole::Primary]);
    }

    public function counter(): static
    {
        return $this->state(['role' => TeamRole::Counter]);
    }

    public function inactive(): static
    {
        return $this->state(['status' => TeamStatus::Inactive]);
    }
}
