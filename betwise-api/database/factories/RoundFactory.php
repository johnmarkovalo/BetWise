<?php

namespace Database\Factories;

use App\Enums\RoundStatus;
use App\Models\Matchup;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Round>
 */
class RoundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'matchup_id' => Matchup::factory(),
            'execute_at' => fake()->dateTimeBetween('now', '+1 hour')->getTimestamp() * 1000,
            'status' => RoundStatus::Preparing,
            'seed' => Str::random(32),
            'total_capital' => fake()->randomFloat(2, 1000, 100000),
        ];
    }

    public function prepared(): static
    {
        return $this->state(['status' => RoundStatus::Prepared]);
    }

    public function executing(): static
    {
        return $this->state(['status' => RoundStatus::Executing]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => RoundStatus::Completed,
            'execute_at' => fake()->dateTimeBetween('-7 days', '-1 hour')->getTimestamp() * 1000,
        ]);
    }

    public function aborted(): static
    {
        return $this->state(['status' => RoundStatus::Aborted]);
    }
}
