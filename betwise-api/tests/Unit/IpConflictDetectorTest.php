<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Device;
use App\Models\DeviceIp;
use App\Models\IpConflictRule;
use App\Models\IpUsageLog;
use App\Models\Team;
use App\Services\IpConflictDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IpConflictDetectorTest extends TestCase
{
    use RefreshDatabase;

    private IpConflictDetector $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IpConflictDetector;
    }

    // =========================================================================
    // checkConcurrentLimit
    // =========================================================================

    #[Test]
    public function check_concurrent_limit_returns_true_when_below_limit(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 3,
        ]);

        DeviceIp::factory()->create(['ip_address' => '10.0.0.1', 'is_active' => true]);
        DeviceIp::factory()->create(['ip_address' => '10.0.0.1', 'is_active' => true]);

        $this->assertTrue($this->service->checkConcurrentLimit('10.0.0.1', 'evolution'));
    }

    #[Test]
    public function check_concurrent_limit_returns_false_when_at_limit(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 2,
        ]);

        DeviceIp::factory()->create(['ip_address' => '10.0.0.1', 'is_active' => true]);
        DeviceIp::factory()->create(['ip_address' => '10.0.0.1', 'is_active' => true]);

        $this->assertFalse($this->service->checkConcurrentLimit('10.0.0.1', 'evolution'));
    }

    #[Test]
    public function check_concurrent_limit_ignores_inactive_device_ips(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 2,
        ]);

        DeviceIp::factory()->create(['ip_address' => '10.0.0.1', 'is_active' => true]);
        DeviceIp::factory()->inactive()->create(['ip_address' => '10.0.0.1']);

        $this->assertTrue($this->service->checkConcurrentLimit('10.0.0.1', 'evolution'));
    }

    // =========================================================================
    // checkCooldown
    // =========================================================================

    #[Test]
    public function check_cooldown_returns_true_when_never_used(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'cooldown_seconds' => 300,
        ]);

        $this->assertTrue($this->service->checkCooldown('10.0.0.2', 'evolution'));
    }

    #[Test]
    public function check_cooldown_returns_true_when_cooldown_has_elapsed(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'cooldown_seconds' => 300,
        ]);

        $deviceIp = DeviceIp::factory()->create(['ip_address' => '10.0.0.2']);
        IpUsageLog::factory()->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subSeconds(400),
        ]);

        $this->assertTrue($this->service->checkCooldown('10.0.0.2', 'evolution'));
    }

    #[Test]
    public function check_cooldown_returns_false_when_still_in_cooldown(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'cooldown_seconds' => 300,
        ]);

        $deviceIp = DeviceIp::factory()->create(['ip_address' => '10.0.0.2']);
        IpUsageLog::factory()->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subSeconds(100),
        ]);

        $this->assertFalse($this->service->checkCooldown('10.0.0.2', 'evolution'));
    }

    // =========================================================================
    // checkHourlyLimit
    // =========================================================================

    #[Test]
    public function check_hourly_limit_returns_true_when_below_limit(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'hourly_limit' => 10,
        ]);

        $deviceIp = DeviceIp::factory()->create(['ip_address' => '10.0.0.3']);
        IpUsageLog::factory()->count(5)->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subMinutes(30),
        ]);

        $this->assertTrue($this->service->checkHourlyLimit('10.0.0.3', 'evolution'));
    }

    #[Test]
    public function check_hourly_limit_returns_false_when_at_limit(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'hourly_limit' => 5,
        ]);

        $deviceIp = DeviceIp::factory()->create(['ip_address' => '10.0.0.3']);
        IpUsageLog::factory()->count(5)->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subMinutes(30),
        ]);

        $this->assertFalse($this->service->checkHourlyLimit('10.0.0.3', 'evolution'));
    }

    #[Test]
    public function check_hourly_limit_ignores_usage_older_than_one_hour(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'hourly_limit' => 3,
        ]);

        $deviceIp = DeviceIp::factory()->create(['ip_address' => '10.0.0.3']);
        IpUsageLog::factory()->count(3)->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subMinutes(90),
        ]);

        $this->assertTrue($this->service->checkHourlyLimit('10.0.0.3', 'evolution'));
    }

    // =========================================================================
    // checkTeamUniqueness
    // =========================================================================

    #[Test]
    public function check_team_uniqueness_returns_true_when_ip_not_used_by_other_teams(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'require_unique_per_team' => true,
        ]);

        $team = Team::factory()->create();
        $account = Account::factory()->create(['team_id' => $team->id]);
        $device = Device::factory()->create(['account_id' => $account->id]);
        DeviceIp::factory()->create(['ip_address' => '10.0.0.4', 'device_id' => $device->id, 'is_active' => true]);

        $this->assertTrue($this->service->checkTeamUniqueness('10.0.0.4', $team->id, 'evolution'));
    }

    #[Test]
    public function check_team_uniqueness_returns_false_when_other_team_uses_same_ip(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'require_unique_per_team' => true,
        ]);

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $accountB = Account::factory()->create(['team_id' => $teamB->id]);
        $deviceB = Device::factory()->create(['account_id' => $accountB->id]);
        DeviceIp::factory()->create(['ip_address' => '10.0.0.4', 'device_id' => $deviceB->id, 'is_active' => true]);

        $this->assertFalse($this->service->checkTeamUniqueness('10.0.0.4', $teamA->id, 'evolution'));
    }

    #[Test]
    public function check_team_uniqueness_returns_true_when_rule_not_enforced(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'require_unique_per_team' => false,
        ]);

        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();

        $accountB = Account::factory()->create(['team_id' => $teamB->id]);
        $deviceB = Device::factory()->create(['account_id' => $accountB->id]);
        DeviceIp::factory()->create(['ip_address' => '10.0.0.4', 'device_id' => $deviceB->id, 'is_active' => true]);

        $this->assertTrue($this->service->checkTeamUniqueness('10.0.0.4', $teamA->id, 'evolution'));
    }

    // =========================================================================
    // detectConflicts
    // =========================================================================

    #[Test]
    public function detect_conflicts_returns_empty_when_no_violations(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 5,
            'cooldown_seconds' => 0,
            'hourly_limit' => 100,
            'require_unique_per_team' => false,
        ]);

        $team = Team::factory()->create();
        $account = Account::factory()->create(['team_id' => $team->id]);
        $device = Device::factory()->create(['account_id' => $account->id]);
        DeviceIp::factory()->create(['ip_address' => '10.1.0.1', 'device_id' => $device->id, 'is_active' => true]);

        $conflicts = $this->service->detectConflicts([$device->id], 'evolution');

        $this->assertEmpty($conflicts);
    }

    #[Test]
    public function detect_conflicts_identifies_concurrent_limit_violation(): void
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
        DeviceIp::factory()->create(['ip_address' => '10.2.0.1', 'device_id' => $deviceA->id, 'is_active' => true]);
        DeviceIp::factory()->create(['ip_address' => '10.2.0.1', 'device_id' => $deviceB->id, 'is_active' => true]);

        $conflicts = $this->service->detectConflicts([$deviceA->id, $deviceB->id], 'evolution');

        $types = array_column($conflicts, 'type');
        $this->assertContains('concurrent_limit_exceeded', $types);
    }

    #[Test]
    public function detect_conflicts_identifies_cooldown_violation(): void
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
            'ip_address' => '10.3.0.1',
            'device_id' => $device->id,
            'is_active' => true,
        ]);
        IpUsageLog::factory()->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subSeconds(60),
        ]);

        $conflicts = $this->service->detectConflicts([$device->id], 'evolution');

        $types = array_column($conflicts, 'type');
        $this->assertContains('cooldown_violation', $types);
    }

    #[Test]
    public function detect_conflicts_identifies_hourly_limit_violation(): void
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
            'ip_address' => '10.4.0.1',
            'device_id' => $device->id,
            'is_active' => true,
        ]);
        IpUsageLog::factory()->count(2)->create([
            'device_ip_id' => $deviceIp->id,
            'provider' => 'evolution',
            'used_at' => now()->subMinutes(10),
        ]);

        $conflicts = $this->service->detectConflicts([$device->id], 'evolution');

        $types = array_column($conflicts, 'type');
        $this->assertContains('hourly_limit_exceeded', $types);
    }

    #[Test]
    public function detect_conflicts_identifies_team_uniqueness_violation(): void
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

        DeviceIp::factory()->create(['ip_address' => '10.5.0.1', 'device_id' => $deviceA->id, 'is_active' => true]);
        DeviceIp::factory()->create(['ip_address' => '10.5.0.1', 'device_id' => $deviceB->id, 'is_active' => true]);

        $conflicts = $this->service->detectConflicts([$deviceA->id], 'evolution');

        $types = array_column($conflicts, 'type');
        $this->assertContains('team_uniqueness_violation', $types);
    }

    #[Test]
    public function detect_conflicts_writes_audit_log_for_each_conflict(): void
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
        DeviceIp::factory()->create(['ip_address' => '10.6.0.1', 'device_id' => $deviceA->id, 'is_active' => true]);
        DeviceIp::factory()->create(['ip_address' => '10.6.0.1', 'device_id' => $deviceB->id, 'is_active' => true]);

        $this->service->detectConflicts([$deviceA->id, $deviceB->id], 'evolution');

        $this->assertDatabaseHas('audit_logs', [
            'actor' => 'system',
            'action' => 'ip_conflict.detected',
        ]);
    }

    #[Test]
    public function detect_conflicts_only_checks_active_device_ips(): void
    {
        IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 1,
            'cooldown_seconds' => 0,
            'hourly_limit' => 100,
            'require_unique_per_team' => false,
        ]);

        $device = Device::factory()->create();
        DeviceIp::factory()->inactive()->create(['ip_address' => '10.7.0.1', 'device_id' => $device->id]);

        $conflicts = $this->service->detectConflicts([$device->id], 'evolution');

        $this->assertEmpty($conflicts);
    }
}
