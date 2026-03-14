<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Capital>
 */
class CapitalFactory extends Factory
{
    public function definition(): array
    {
        $balance = fake()->randomFloat(2, 500, 50000);
        $locked = fake()->randomFloat(2, 0, min($balance, 5000));

        return [
            'account_id' => Account::factory(),
            'balance' => $balance,
            'locked' => $locked,
        ];
    }

    public function empty(): static
    {
        return $this->state(['balance' => '0.00', 'locked' => '0.00']);
    }
}
