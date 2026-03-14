<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IpConflictRule>
 */
class IpConflictRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => fake()->unique()->randomElement(['evolution', 'pragmatic', 'playtech', 'microgaming', 'netent']),
            'max_concurrent_devices' => fake()->numberBetween(1, 10),
            'cooldown_seconds' => fake()->randomElement([30, 60, 120, 300]),
            'hourly_limit' => fake()->numberBetween(10, 100),
            'require_unique_per_team' => fake()->boolean(),
        ];
    }
}
