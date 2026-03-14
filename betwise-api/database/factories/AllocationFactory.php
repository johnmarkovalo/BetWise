<?php

namespace Database\Factories;

use App\Enums\AllocationOutcome;
use App\Enums\MatchupSide;
use App\Models\Account;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Allocation>
 */
class AllocationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'round_id' => Round::factory(),
            'account_id' => Account::factory(),
            'side' => fake()->randomElement(MatchupSide::cases()),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'outcome' => null,
            'payout' => null,
            'executed_at' => null,
        ];
    }

    public function settled(): static
    {
        $outcome = fake()->randomElement(AllocationOutcome::cases());
        $amount = fake()->randomFloat(2, 10, 5000);

        return $this->state([
            'outcome' => $outcome,
            'payout' => $outcome === AllocationOutcome::Win ? $amount * 1.95 : 0,
            'executed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
