<?php

namespace Database\Factories;

use App\Enums\IpType;
use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeviceIp>
 */
class DeviceIpFactory extends Factory
{
    public function definition(): array
    {
        $activeFrom = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'device_id' => Device::factory(),
            'ip_address' => fake()->ipv4(),
            'ip_type' => IpType::Direct,
            'proxy_config' => null,
            'active_from' => $activeFrom,
            'active_until' => null,
            'is_active' => true,
        ];
    }

    public function proxy(): static
    {
        return $this->state([
            'ip_type' => IpType::Proxy,
            'proxy_config' => [
                'host' => fake()->ipv4(),
                'port' => fake()->numberBetween(1024, 65535),
                'protocol' => 'socks5',
            ],
        ]);
    }

    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
            'active_until' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}
