<?php

namespace Database\Factories;

use App\Enums\ProxyProtocol;
use App\Enums\ProxyStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProxyPool>
 */
class ProxyPoolFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ip_address' => fake()->unique()->ipv4(),
            'port' => fake()->numberBetween(1024, 65535),
            'protocol' => fake()->randomElement(ProxyProtocol::cases()),
            'username' => fake()->userName(),
            'password_encrypted' => Crypt::encryptString(fake()->password()),
            'geographic_region' => fake()->randomElement(['US', 'EU', 'AS', 'AU']),
            'status' => ProxyStatus::Active,
            'health_score' => fake()->randomFloat(2, 0.5, 1.0),
            'total_uses' => fake()->numberBetween(0, 10000),
            'failed_uses' => fake()->numberBetween(0, 100),
            'banned_by_providers' => [],
            'last_health_check' => fake()->dateTimeBetween('-1 hour', 'now'),
        ];
    }

    public function degraded(): static
    {
        return $this->state([
            'status' => ProxyStatus::Degraded,
            'health_score' => fake()->randomFloat(2, 0.3, 0.7),
        ]);
    }

    public function disabled(): static
    {
        return $this->state([
            'status' => ProxyStatus::Disabled,
            'health_score' => fake()->randomFloat(2, 0.0, 0.3),
        ]);
    }
}
