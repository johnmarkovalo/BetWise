<?php

namespace Tests\Feature\Api\V1;

use App\Models\IpConflictRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IpConflictRuleTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // GET /api/v1/ip-conflict-rules
    // =========================================================================

    #[Test]
    public function index_returns_paginated_rules(): void
    {
        IpConflictRule::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/ip-conflict-rules');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'provider', 'max_concurrent_devices', 'cooldown_seconds', 'hourly_limit', 'require_unique_per_team']],
                'meta',
                'links',
            ])
            ->assertJsonCount(3, 'data');
    }

    // =========================================================================
    // GET /api/v1/ip-conflict-rules/{ip_conflict_rule}
    // =========================================================================

    #[Test]
    public function show_returns_rule(): void
    {
        $rule = IpConflictRule::factory()->create(['provider' => 'evolution']);

        $response = $this->getJson("/api/v1/ip-conflict-rules/{$rule->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $rule->id)
            ->assertJsonPath('data.provider', 'evolution');
    }

    #[Test]
    public function show_returns_404_for_unknown_rule(): void
    {
        $this->getJson('/api/v1/ip-conflict-rules/00000000-0000-0000-0000-000000000000')
            ->assertNotFound();
    }

    // =========================================================================
    // POST /api/v1/ip-conflict-rules
    // =========================================================================

    #[Test]
    public function store_creates_rule_and_returns_201(): void
    {
        $response = $this->postJson('/api/v1/ip-conflict-rules', [
            'provider' => 'evolution',
            'max_concurrent_devices' => 3,
            'cooldown_seconds' => 300,
            'hourly_limit' => 50,
            'require_unique_per_team' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.provider', 'evolution')
            ->assertJsonPath('data.max_concurrent_devices', 3)
            ->assertJsonPath('data.require_unique_per_team', true);

        $this->assertDatabaseHas('ip_conflict_rules', ['provider' => 'evolution']);
    }

    #[Test]
    public function store_fails_validation_when_provider_missing(): void
    {
        $this->postJson('/api/v1/ip-conflict-rules', [
            'max_concurrent_devices' => 3,
            'cooldown_seconds' => 300,
            'hourly_limit' => 50,
            'require_unique_per_team' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    #[Test]
    public function store_fails_validation_when_provider_already_exists(): void
    {
        IpConflictRule::factory()->create(['provider' => 'evolution']);

        $this->postJson('/api/v1/ip-conflict-rules', [
            'provider' => 'evolution',
            'max_concurrent_devices' => 5,
            'cooldown_seconds' => 60,
            'hourly_limit' => 100,
            'require_unique_per_team' => false,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    #[Test]
    public function store_fails_validation_when_max_concurrent_devices_is_zero(): void
    {
        $this->postJson('/api/v1/ip-conflict-rules', [
            'provider' => 'pragmatic',
            'max_concurrent_devices' => 0,
            'cooldown_seconds' => 300,
            'hourly_limit' => 50,
            'require_unique_per_team' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_concurrent_devices']);
    }

    #[Test]
    public function store_fails_validation_when_hourly_limit_is_zero(): void
    {
        $this->postJson('/api/v1/ip-conflict-rules', [
            'provider' => 'pragmatic',
            'max_concurrent_devices' => 3,
            'cooldown_seconds' => 300,
            'hourly_limit' => 0,
            'require_unique_per_team' => true,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hourly_limit']);
    }

    // =========================================================================
    // PUT /api/v1/ip-conflict-rules/{ip_conflict_rule}
    // =========================================================================

    #[Test]
    public function update_modifies_rule(): void
    {
        $rule = IpConflictRule::factory()->create([
            'provider' => 'evolution',
            'max_concurrent_devices' => 3,
        ]);

        $response = $this->putJson("/api/v1/ip-conflict-rules/{$rule->id}", [
            'max_concurrent_devices' => 5,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.max_concurrent_devices', 5);

        $this->assertDatabaseHas('ip_conflict_rules', ['id' => $rule->id, 'max_concurrent_devices' => 5]);
    }

    #[Test]
    public function update_allows_keeping_same_provider(): void
    {
        $rule = IpConflictRule::factory()->create(['provider' => 'evolution']);

        $this->putJson("/api/v1/ip-conflict-rules/{$rule->id}", [
            'provider' => 'evolution',
            'cooldown_seconds' => 600,
        ])->assertOk();
    }

    #[Test]
    public function update_fails_validation_when_provider_taken_by_another_rule(): void
    {
        IpConflictRule::factory()->create(['provider' => 'evolution']);
        $rule = IpConflictRule::factory()->create(['provider' => 'pragmatic']);

        $this->putJson("/api/v1/ip-conflict-rules/{$rule->id}", ['provider' => 'evolution'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    // =========================================================================
    // DELETE /api/v1/ip-conflict-rules/{ip_conflict_rule}
    // =========================================================================

    #[Test]
    public function destroy_deletes_rule_and_returns_204(): void
    {
        $rule = IpConflictRule::factory()->create();

        $this->deleteJson("/api/v1/ip-conflict-rules/{$rule->id}")->assertNoContent();

        $this->assertDatabaseMissing('ip_conflict_rules', ['id' => $rule->id]);
    }
}
