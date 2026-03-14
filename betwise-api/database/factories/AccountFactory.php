<?php

namespace Database\Factories;

use App\Enums\AccountStatus;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'provider' => fake()->randomElement(['evolution', 'pragmatic', 'playtech', 'microgaming']),
            'commission_pct' => fake()->randomFloat(2, 0.5, 5.0),
            'min_balance_threshold' => fake()->randomFloat(2, 100, 1000),
            'status' => AccountStatus::Active,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => AccountStatus::Inactive]);
    }

    public function paused(): static
    {
        return $this->state(['status' => AccountStatus::Paused]);
    }
}
