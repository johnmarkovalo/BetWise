<?php

namespace Tests\Feature\Api\V1;

use App\Enums\IpType;
use App\Enums\ProxyStatus;
use App\Models\Device;
use App\Models\DeviceIp;
use App\Models\ProxyPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceIpTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // POST /api/v1/devices/{device}/ip/rotate — happy path
    // =========================================================================

    #[Test]
    public function rotate_returns_new_device_ip_resource(): void
    {
        $proxy = ProxyPool::factory()->create([
            'ip_address' => '203.0.113.99',
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();

        $response = $this->postJson("/api/v1/devices/{$device->id}/ip/rotate", [
            'provider' => 'evolution',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.device_id', $device->id)
            ->assertJsonPath('data.ip_address', $proxy->ip_address)
            ->assertJsonPath('data.ip_type', IpType::Proxy->value)
            ->assertJsonPath('data.is_active', true);
    }

    #[Test]
    public function rotate_creates_new_device_ip_record_in_database(): void
    {
        ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();

        $this->postJson("/api/v1/devices/{$device->id}/ip/rotate", [
            'provider' => 'evolution',
        ])->assertOk();

        $this->assertDatabaseHas('device_ips', [
            'device_id' => $device->id,
            'ip_type' => IpType::Proxy->value,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function rotate_deactivates_old_device_ip_record(): void
    {
        ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();
        $oldIp = DeviceIp::factory()->create(['device_id' => $device->id, 'is_active' => true]);

        $this->postJson("/api/v1/devices/{$device->id}/ip/rotate", [
            'provider' => 'evolution',
        ])->assertOk();

        $this->assertDatabaseHas('device_ips', [
            'id' => $oldIp->id,
            'is_active' => false,
        ]);
        $this->assertNotNull($oldIp->fresh()->active_until);
    }

    #[Test]
    public function rotate_stores_proxy_config_on_new_record(): void
    {
        $proxy = ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();

        $response = $this->postJson("/api/v1/devices/{$device->id}/ip/rotate", [
            'provider' => 'evolution',
        ]);

        $response->assertOk();
        $config = $response->json('data.proxy_config');
        $this->assertEquals($proxy->id, $config['proxy_id']);
        $this->assertArrayHasKey('configured_at', $config);
    }

    // =========================================================================
    // POST /api/v1/devices/{device}/ip/rotate — no proxy available
    // =========================================================================

    #[Test]
    public function rotate_returns_503_when_no_proxy_available(): void
    {
        $device = Device::factory()->create();

        $this->postJson("/api/v1/devices/{$device->id}/ip/rotate", [
            'provider' => 'evolution',
        ])->assertServiceUnavailable();
    }

    // =========================================================================
    // POST /api/v1/devices/{device}/ip/rotate — validation
    // =========================================================================

    #[Test]
    public function rotate_fails_validation_when_provider_missing(): void
    {
        $device = Device::factory()->create();

        $this->postJson("/api/v1/devices/{$device->id}/ip/rotate", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    #[Test]
    public function rotate_returns_404_for_unknown_device(): void
    {
        $this->postJson('/api/v1/devices/00000000-0000-0000-0000-000000000000/ip/rotate', [
            'provider' => 'evolution',
        ])->assertNotFound();
    }

    #[Test]
    public function rotate_accepts_optional_preferred_region(): void
    {
        ProxyPool::factory()->create([
            'status' => ProxyStatus::Active,
            'health_score' => 0.90,
            'geographic_region' => 'US',
            'banned_by_providers' => [],
        ]);
        $device = Device::factory()->create();

        $this->postJson("/api/v1/devices/{$device->id}/ip/rotate", [
            'provider' => 'evolution',
            'preferred_region' => 'US',
        ])->assertOk();
    }
}
