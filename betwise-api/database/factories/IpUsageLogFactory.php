<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\DeviceIp;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IpUsageLog>
 */
class IpUsageLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'device_ip_id' => DeviceIp::factory(),
            'provider' => fake()->randomElement(['evolution', 'pragmatic', 'playtech', 'microgaming']),
            'account_id' => Account::factory(),
            'round_id' => Round::factory(),
            'used_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'success' => true,
            'flagged' => false,
        ];
    }

    public function failed(): static
    {
        return $this->state(['success' => false]);
    }

    public function flagged(): static
    {
        return $this->state(['flagged' => true, 'success' => false]);
    }
}
