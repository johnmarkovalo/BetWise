<?php

namespace Database\Factories;

use App\Enums\MatchupStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Matchup>
 */
class MatchupFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => fake()->randomElement(['evolution', 'pragmatic', 'playtech', 'microgaming']),
            'table_id' => strtoupper(fake()->bothify('TABLE-????-##')),
            'status' => MatchupStatus::Active,
            'locked_at' => null,
        ];
    }

    public function locked(): static
    {
        return $this->state(['locked_at' => now()]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => MatchupStatus::Completed,
            'locked_at' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
