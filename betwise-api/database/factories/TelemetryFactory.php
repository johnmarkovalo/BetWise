<?php

namespace Database\Factories;

use App\Models\Device;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Telemetry>
 */
class TelemetryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_id' => Device::factory(),
            'round_id' => Round::factory(),
            'execution_time_ms' => fake()->numberBetween(50, 2000),
            'time_drift_ms' => fake()->numberBetween(-100, 100),
            'bet_placed' => true,
            'battery_level' => fake()->numberBetween(10, 100),
            'network_type' => fake()->randomElement(['wifi', '4g', '5g', 'lte']),
            'app_version' => fake()->semver(),
            'error_message' => null,
        ];
    }

    public function failed(): static
    {
        return $this->state([
            'bet_placed' => false,
            'error_message' => fake()->sentence(),
        ]);
    }

    public function withDrift(): static
    {
        return $this->state([
            'time_drift_ms' => fake()->numberBetween(50, 500),
        ]);
    }
}
