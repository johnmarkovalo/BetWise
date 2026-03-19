<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProxyStatus;
use App\Models\ProxyPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProxyTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // GET /api/v1/proxies
    // =========================================================================

    #[Test]
    public function index_returns_paginated_proxies(): void
    {
        ProxyPool::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/proxies');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'ip_address', 'port', 'protocol', 'status', 'health_score']],
                'meta',
                'links',
            ])
            ->assertJsonCount(3, 'data');
    }

    // =========================================================================
    // GET /api/v1/proxies/{proxy}
    // =========================================================================

    #[Test]
    public function show_returns_proxy(): void
    {
        $proxy = ProxyPool::factory()->create(['ip_address' => '10.10.10.10']);

        $response = $this->getJson("/api/v1/proxies/{$proxy->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $proxy->id)
            ->assertJsonPath('data.ip_address', '10.10.10.10');
    }

    #[Test]
    public function show_returns_404_for_unknown_proxy(): void
    {
        $this->getJson('/api/v1/proxies/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    #[Test]
    public function show_does_not_expose_password(): void
    {
        $proxy = ProxyPool::factory()->create();

        $response = $this->getJson("/api/v1/proxies/{$proxy->id}");

        $response->assertOk();
        $this->assertArrayNotHasKey('password_encrypted', $response->json('data'));
    }

    // =========================================================================
    // POST /api/v1/proxies
    // =========================================================================

    #[Test]
    public function store_creates_proxy_and_returns_201(): void
    {
        $response = $this->postJson('/api/v1/proxies', [
            'ip_address' => '203.0.113.50',
            'port' => 8080,
            'protocol' => 'socks5',
            'username' => 'proxyuser',
            'password' => 'secret',
            'geographic_region' => 'US',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.ip_address', '203.0.113.50')
            ->assertJsonPath('data.protocol', 'socks5');

        $this->assertDatabaseHas('proxy_pool', ['ip_address' => '203.0.113.50']);
    }

    #[Test]
    public function store_fails_validation_when_ip_missing(): void
    {
        $this->postJson('/api/v1/proxies', ['port' => 8080, 'protocol' => 'socks5'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ip_address']);
    }

    #[Test]
    public function store_fails_validation_when_protocol_invalid(): void
    {
        $this->postJson('/api/v1/proxies', [
            'ip_address' => '203.0.113.51',
            'port' => 8080,
            'protocol' => 'ftp',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['protocol']);
    }

    #[Test]
    public function store_fails_validation_when_ip_already_exists(): void
    {
        ProxyPool::factory()->create(['ip_address' => '203.0.113.52']);

        $this->postJson('/api/v1/proxies', [
            'ip_address' => '203.0.113.52',
            'port' => 9090,
            'protocol' => 'http',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ip_address']);
    }

    // =========================================================================
    // PUT /api/v1/proxies/{proxy}
    // =========================================================================

    #[Test]
    public function update_modifies_proxy(): void
    {
        $proxy = ProxyPool::factory()->create(['geographic_region' => 'EU']);

        $response = $this->putJson("/api/v1/proxies/{$proxy->id}", [
            'geographic_region' => 'US',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.geographic_region', 'US');

        $this->assertDatabaseHas('proxy_pool', ['id' => $proxy->id, 'geographic_region' => 'US']);
    }

    #[Test]
    public function update_fails_validation_when_protocol_invalid(): void
    {
        $proxy = ProxyPool::factory()->create();

        $this->putJson("/api/v1/proxies/{$proxy->id}", ['protocol' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['protocol']);
    }

    // =========================================================================
    // DELETE /api/v1/proxies/{proxy}
    // =========================================================================

    #[Test]
    public function destroy_deletes_proxy_and_returns_204(): void
    {
        $proxy = ProxyPool::factory()->create();

        $this->deleteJson("/api/v1/proxies/{$proxy->id}")->assertNoContent();

        $this->assertDatabaseMissing('proxy_pool', ['id' => $proxy->id]);
    }

    // =========================================================================
    // Proxy selection ordering (health score, region, banned-provider exclusion)
    // =========================================================================

    #[Test]
    public function index_returns_active_and_degraded_proxies(): void
    {
        ProxyPool::factory()->create(['status' => ProxyStatus::Active]);
        ProxyPool::factory()->degraded()->create();
        ProxyPool::factory()->disabled()->create();

        $response = $this->getJson('/api/v1/proxies');

        // Index returns all proxies regardless of status
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    #[Test]
    public function store_encrypts_password_and_does_not_return_it(): void
    {
        $response = $this->postJson('/api/v1/proxies', [
            'ip_address' => '203.0.113.60',
            'port' => 1080,
            'protocol' => 'socks5',
            'password' => 'verysecret',
        ]);

        $response->assertCreated();
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('password_encrypted', $response->json('data'));

        // Verify encrypted value is stored
        $this->assertDatabaseHas('proxy_pool', ['ip_address' => '203.0.113.60']);
        $proxy = ProxyPool::query()->where('ip_address', '203.0.113.60')->first();
        $this->assertNotNull($proxy->password_encrypted);
        $this->assertNotEquals('verysecret', $proxy->password_encrypted);
    }
}
