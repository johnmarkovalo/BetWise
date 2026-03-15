<?php

namespace Tests\Feature\Api\V1;

use App\Models\Account;
use App\Models\Device;
use App\Models\DeviceIp;
use App\Models\IpConflictRule;
use App\Models\IpUsageLog;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IpConflictTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // POST /api/v1/ip-conflicts/check — happy path
    // =========================================================================

    #[Test]
    public function check_returns_empty_conflicts_when_no_violations(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 5,
            'cooldown_seconds' => 0,
            'hourly_limit' => 100,
            'require_unique_per_team' => false,
        ]);

        $device = Device::factory()->create();
        DeviceIp::factory()->create(['device_id' => $device->id, 'ip_address' => '10.0.1.1', 'is_active' => true]);

        $response = $this->postJson('/api/v1/ip-conflicts/check', [
            'device_ids' => [$device->id],
            'provider' => 'evolution',
        ]);

        $response->assertOk()
            ->assertJsonPath('safe_to_proceed', true)
            ->assertJsonCount(0, 'conflicts');
    }

    // =========================================================================
    // POST /api/v1/ip-conflicts/check — rule violations
    // =========================================================================

    #[Test]
    public function check_detects_concurrent_limit_violation(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 1,
            'cooldown_seconds' => 0,
            'hourly_limit' => 100,
            'require_unique_per_team' => false,
        ]);

        $deviceA = Device::factory()->create();
        $deviceB = Device::factory()->create();
        DeviceIp::factory()->create(['device_id' => $deviceA->id, 'ip_address' => '10.0.2.1', 'is_active' => true]);
        DeviceIp::factory()->create(['device_id' => $deviceB->id, 'ip_address' => '10.0.2.1', 'is_active' => true]);

        $response = $this->postJson('/api/v1/ip-conflicts/check', [
            'device_ids' => [$deviceA->id, $deviceB->id],
            'provider' => 'evolution',
        ]);

        $response->assertOk()
            ->assertJsonPath('safe_to_proceed', false);

        $types = array_column($response->json('conflicts'), 'type');
        $this->assertContains('concurrent_limit_exceeded', $types);
    }

    #[Test]
    public function check_detects_cooldown_violation(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 5,
            'cooldown_seconds' => 300,
            'hourly_limit' => 100,
            'require_unique_per_team' => false,
        ]);

        $device = Device::factory()->create();
        $deviceIp = DeviceIp::factory()->create([
            'device_id' => $device->id,
            'ip_address' => '10.0.3.1',
            'is_active' => true,
        ]);
        IpUsageLog::factory()->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subSeconds(60),
        ]);

        $response = $this->postJson('/api/v1/ip-conflicts/check', [
            'device_ids' => [$device->id],
            'provider' => 'evolution',
        ]);

        $response->assertOk()
            ->assertJsonPath('safe_to_proceed', false);

        $types = array_column($response->json('conflicts'), 'type');
        $this->assertContains('cooldown_violation', $types);
    }

    #[Test]
    public function check_detects_hourly_limit_violation(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 5,
            'cooldown_seconds' => 0,
            'hourly_limit' => 2,
            'require_unique_per_team' => false,
        ]);

        $device = Device::factory()->create();
        $deviceIp = DeviceIp::factory()->create([
            'device_id' => $device->id,
            'ip_address' => '10.0.4.1',
            'is_active' => true,
        ]);
        IpUsageLog::factory()->count(2)->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subMinutes(10),
        ]);

        $response = $this->postJson('/api/v1/ip-conflicts/check', [
            'device_ids' => [$device->id],
            'provider' => 'evolution',
        ]);

        $response->assertOk()
            ->assertJsonPath('safe_to_proceed', false);

        $types = array_column($response->json('conflicts'), 'type');
        $this->assertContains('hourly_limit_exceeded', $types);
    }

    #[Test]
    public function check_detects_team_uniqueness_violation(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 5,
            'cooldown_seconds' => 0,
            'hourly_limit' => 100,
            'require_unique_per_team' => true,
        ]);

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $accountA = Account::factory()->create(['team_id' => $teamA->id]);
        $accountB = Account::factory()->create(['team_id' => $teamB->id]);
        $deviceA = Device::factory()->create(['account_id' => $accountA->id]);
        $deviceB = Device::factory()->create(['account_id' => $accountB->id]);

        DeviceIp::factory()->create(['device_id' => $deviceA->id, 'ip_address' => '10.0.5.1', 'is_active' => true]);
        DeviceIp::factory()->create(['device_id' => $deviceB->id, 'ip_address' => '10.0.5.1', 'is_active' => true]);

        $response = $this->postJson('/api/v1/ip-conflicts/check', [
            'device_ids' => [$deviceA->id],
            'provider' => 'evolution',
        ]);

        $response->assertOk()
            ->assertJsonPath('safe_to_proceed', false);

        $types = array_column($response->json('conflicts'), 'type');
        $this->assertContains('team_uniqueness_violation', $types);
    }

    // =========================================================================
    // POST /api/v1/ip-conflicts/check — validation
    // =========================================================================

    #[Test]
    public function check_fails_validation_when_device_ids_missing(): void
    {
        $this->postJson('/api/v1/ip-conflicts/check', ['provider' => 'evolution'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_ids']);
    }

    #[Test]
    public function check_fails_validation_when_provider_missing(): void
    {
        $device = Device::factory()->create();

        $this->postJson('/api/v1/ip-conflicts/check', ['device_ids' => [$device->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    #[Test]
    public function check_fails_validation_for_unknown_device_id(): void
    {
        $this->postJson('/api/v1/ip-conflicts/check', [
            'device_ids' => ['00000000-0000-0000-0000-000000000000'],
            'provider' => 'evolution',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['device_ids.0']);
    }
}
