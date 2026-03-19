<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        $actions = ['round.created', 'round.executed', 'round.aborted', 'account.updated', 'device.connected', 'allocation.settled'];
        $entities = ['Round', 'Account', 'Device', 'Allocation', 'Matchup'];

        return [
            'actor' => fake()->randomElement(['system', 'admin', 'scheduler']),
            'action' => fake()->randomElement($actions),
            'entity_type' => $entity = fake()->randomElement($entities),
            'entity_id' => Str::uuid()->toString(),
            'payload' => ['entity' => strtolower($entity), 'timestamp' => now()->toIso8601String()],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }
}
