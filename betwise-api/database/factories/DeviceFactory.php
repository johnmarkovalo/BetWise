<?php

namespace Database\Factories;

use App\Enums\DeviceStatus;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'name' => 'Device-'.fake()->bothify('??##'),
            'android_id' => strtolower(Str::random(16)),
            'auth_token' => Str::random(64),
            'status' => DeviceStatus::Offline,
            'last_seen' => fake()->dateTimeBetween('-7 days', 'now'),
            'battery_level' => fake()->numberBetween(10, 100),
            'app_version' => fake()->semver(),
        ];
    }

    public function online(): static
    {
        return $this->state([
            'status' => DeviceStatus::Online,
            'last_seen' => now(),
        ]);
    }

    public function error(): static
    {
        return $this->state(['status' => DeviceStatus::Error]);
    }
}
