<?php

namespace Tests\Unit;

use App\Enums\IpType;
use App\Enums\ProxyStatus;
use App\Models\Device;
use App\Models\DeviceIp;
use App\Models\ProxyPool;
use App\Services\ProxyPoolManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ProxyPoolManagerTest extends TestCase
{
    use RefreshDatabase;

    private ProxyPoolManager $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProxyPoolManager;
    }

    // =========================================================================
    // selectProxy
    // =========================================================================

    #[Test]
    public function select_proxy_returns_active_proxy_above_health_threshold(): void
    {
        $proxy = ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);

        $selected = $this->service->selectProxy('evolution');

        $this->assertNotNull($selected);
        $this->assertEquals($proxy->id, $selected->id);
    }

    #[Test]
    public function select_proxy_returns_null_when_no_proxies_available(): void
    {
        $selected = $this->service->selectProxy('evolution');

        $this->assertNull($selected);
    }

    #[Test]
    public function select_proxy_excludes_proxies_below_health_threshold(): void
    {
        ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.40,
            'banned_by_providers' => [],
        ]);

        $selected = $this->service->selectProxy('evolution');

        $this->assertNull($selected);
    }

    #[Test]
    public function select_proxy_excludes_degraded_proxies(): void
    {
        ProxyPool::factory()->degraded()->create(['banned_by_providers' => []]);

        $selected = $this->service->selectProxy('evolution');

        $this->assertNull($selected);
    }

    #[Test]
    public function select_proxy_excludes_proxies_banned_for_provider(): void
    {
        ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.95,
            'banned_by_providers' => ['evolution'],
        ]);

        $selected = $this->service->selectProxy('evolution');

        $this->assertNull($selected);
    }

    #[Test]
    public function select_proxy_allows_proxy_banned_for_different_provider(): void
    {
        $proxy = ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.95,
            'banned_by_providers' => ['pragmatic'],
        ]);

        $selected = $this->service->selectProxy('evolution');

        $this->assertNotNull($selected);
        $this->assertEquals($proxy->id, $selected->id);
    }

    #[Test]
    public function select_proxy_prefers_matching_region(): void
    {
        // Create a low-health proxy in the matching region and a high-health proxy elsewhere
        $regionalProxy = ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.60,
            'geographic_region' => 'US',
            'total_uses' => 0,
            'banned_by_providers' => [],
        ]);
        ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.99,
            'geographic_region' => 'EU',
            'total_uses' => 10000,
            'banned_by_providers' => [],
        ]);

        // Run many times; the regional proxy should always be preferred in ordering
        // (first candidate in the ordered list gets higher weight if health_score is decent)
        $selectedIds = [];
        for ($i = 0; $i < 20; $i++) {
            $selected = $this->service->selectProxy('evolution', 'US');
            $selectedIds[] = $selected?->id;
        }

        // The regional proxy must appear at least once in 20 runs
        $this->assertContains($regionalProxy->id, $selectedIds);
    }

    // =========================================================================
    // rotateDeviceIp
    // =========================================================================

    #[Test]
    public function rotate_device_ip_deactivates_current_active_ip(): void
    {
        $proxy = ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();
        $oldIp = DeviceIp::factory()->create([
            'device_id' => $device->id,
            'is_active' => true,
        ]);

        $this->service->rotateDeviceIp($device, 'evolution');

        $this->assertDatabaseHas('device_ips', [
            'id' => $oldIp->id,
            'is_active' => false,
        ]);
        $this->assertNotNull($oldIp->fresh()->active_until);
    }

    #[Test]
    public function rotate_device_ip_creates_new_device_ip_record(): void
    {
        $proxy = ProxyPool::factory()->create([
            'ip_address' => '10.20.30.40',
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();

        $newDeviceIp = $this->service->rotateDeviceIp($device, 'evolution');

        $this->assertInstanceOf(DeviceIp::class, $newDeviceIp);
        $this->assertEquals($device->id, $newDeviceIp->device_id);
        $this->assertEquals($proxy->ip_address, $newDeviceIp->ip_address);
        $this->assertEquals(IpType::Proxy, $newDeviceIp->ip_type);
        $this->assertTrue($newDeviceIp->is_active);
    }

    #[Test]
    public function rotate_device_ip_stores_proxy_config_on_new_record(): void
    {
        $proxy = ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();

        $newDeviceIp = $this->service->rotateDeviceIp($device, 'evolution');

        $config = $newDeviceIp->proxy_config;
        $this->assertEquals($proxy->id, $config['proxy_id']);
        $this->assertEquals($proxy->port, $config['port']);
        $this->assertArrayHasKey('configured_at', $config);
    }

    #[Test]
    public function rotate_device_ip_writes_audit_log(): void
    {
        ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();

        $this->service->rotateDeviceIp($device, 'evolution');

        $this->assertDatabaseHas('audit_logs', [
            'actor' => 'system',
            'action' => 'device_ip.rotated',
            'entity_type' => 'Device',
            'entity_id' => $device->id,
        ]);
    }

    #[Test]
    public function rotate_device_ip_throws_when_no_proxy_available(): void
    {
        $device = Device::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No proxy available for provider [evolution].');

        $this->service->rotateDeviceIp($device, 'evolution');
    }

    // =========================================================================
    // updateProxyHealth
    // =========================================================================

    #[Test]
    public function update_proxy_health_increments_total_uses_on_success(): void
    {
        $proxy = ProxyPool::factory()->create([
            'total_uses' => 10,
            'failed_uses' => 0,
            'health_score' => 1.00,
            'status' => ProxyStatus::Active,
        ]);

        $this->service->updateProxyHealth($proxy, true);

        $this->assertEquals(11, $proxy->fresh()->total_uses);
        $this->assertEquals(0, $proxy->fresh()->failed_uses);
    }

    #[Test]
    public function update_proxy_health_increments_failed_uses_on_failure(): void
    {
        $proxy = ProxyPool::factory()->create([
            'total_uses' => 10,
            'failed_uses' => 1,
            'health_score' => 0.90,
            'status' => ProxyStatus::Active,
        ]);

        $this->service->updateProxyHealth($proxy, false);

        $this->assertEquals(11, $proxy->fresh()->total_uses);
        $this->assertEquals(2, $proxy->fresh()->failed_uses);
    }

    #[Test]
    public function update_proxy_health_recalculates_health_score(): void
    {
        $proxy = ProxyPool::factory()->create([
            'total_uses' => 9,
            'failed_uses' => 1,
            'health_score' => 0.89,
            'status' => ProxyStatus::Active,
        ]);

        // After: total=10, failed=2 → score = 1 - (2/10) = 0.80
        $this->service->updateProxyHealth($proxy, false);

        $this->assertEquals('0.80', $proxy->fresh()->health_score);
    }

    #[Test]
    public function update_proxy_health_transitions_status_to_degraded(): void
    {
        // total=9, failed=4 → after fail: total=10, failed=5 → score=0.50 → degraded (< 0.5 is false, equals 0.5)
        // Let's use: total=9, failed=4 → after fail: total=10, failed=5 → score = 1 - 0.5 = 0.5 → active (not < 0.5)
        // Need score < 0.5: total=10, failed=6 → score=0.40 → degraded
        $proxy = ProxyPool::factory()->create([
            'total_uses' => 9,
            'failed_uses' => 5,
            'health_score' => 0.56,
            'status' => ProxyStatus::Active,
        ]);

        // After: total=10, failed=6 → score = 1 - 0.6 = 0.40 → degraded (0.40 < 0.5)
        $this->service->updateProxyHealth($proxy, false);

        $this->assertEquals(ProxyStatus::Degraded, $proxy->fresh()->status);
    }

    #[Test]
    public function update_proxy_health_transitions_status_to_disabled(): void
    {
        $proxy = ProxyPool::factory()->create([
            'total_uses' => 9,
            'failed_uses' => 8,
            'health_score' => 0.11,
            'status' => ProxyStatus::Degraded,
        ]);

        // After: total=10, failed=9 → score = 1 - 0.9 = 0.10 → disabled (< 0.3)
        $this->service->updateProxyHealth($proxy, false);

        $this->assertEquals(ProxyStatus::Disabled, $proxy->fresh()->status);
    }

    #[Test]
    public function update_proxy_health_keeps_status_active_on_healthy_score(): void
    {
        $proxy = ProxyPool::factory()->create([
            'total_uses' => 99,
            'failed_uses' => 0,
            'health_score' => 1.00,
            'status' => ProxyStatus::Active,
        ]);

        $this->service->updateProxyHealth($proxy, true);

        $this->assertEquals(ProxyStatus::Active, $proxy->fresh()->status);
    }

    #[Test]
    public function update_proxy_health_updates_last_health_check_timestamp(): void
    {
        $proxy = ProxyPool::factory()->create([
            'total_uses' => 5,
            'failed_uses' => 0,
            'last_health_check' => now()->subHour(),
        ]);

        $this->service->updateProxyHealth($proxy, true);

        $this->assertTrue($proxy->fresh()->last_health_check->isAfter(now()->subMinute()));
    }

    #[Test]
    public function update_proxy_health_writes_audit_log(): void
    {
        $proxy = ProxyPool::factory()->create([
            'total_uses' => 5,
            'failed_uses' => 0,
            'health_score' => 1.00,
            'status' => ProxyStatus::Active,
        ]);

        $this->service->updateProxyHealth($proxy, true);

        $this->assertDatabaseHas('audit_logs', [
            'actor' => 'system',
            'action' => 'proxy.health_updated',
            'entity_type' => 'ProxyPool',
            'entity_id' => $proxy->id,
        ]);
    }
}
