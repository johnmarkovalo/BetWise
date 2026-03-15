<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AccountStatus;
use App\Enums\TeamStatus;
use App\Models\Account;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamApiTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // GET /api/v1/teams
    // =========================================================================

    #[Test]
    public function index_returns_paginated_teams(): void
    {
        Team::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/teams');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'role', 'status', 'created_at']],
                'meta',
                'links',
            ])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function index_filters_by_status(): void
    {
        Team::factory()->count(2)->active()->create();
        Team::factory()->create(['status' => TeamStatus::Inactive]);

        $response = $this->getJson('/api/v1/teams?status=active');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function index_filters_by_role(): void
    {
        Team::factory()->count(2)->primary()->create();
        Team::factory()->counter()->create();

        $response = $this->getJson('/api/v1/teams?role=PRIMARY');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    // =========================================================================
    // GET /api/v1/teams/{team}
    // =========================================================================

    #[Test]
    public function show_returns_team_with_accounts(): void
    {
        $team = Team::factory()->create();
        Account::factory()->count(2)->create(['team_id' => $team->id]);

        $response = $this->getJson("/api/v1/teams/{$team->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $team->id)
            ->assertJsonPath('data.account_count', 2);
    }

    #[Test]
    public function show_returns_404_for_unknown_team(): void
    {
        $this->getJson('/api/v1/teams/non-existent-id')->assertNotFound();
    }

    // =========================================================================
    // POST /api/v1/teams
    // =========================================================================

    #[Test]
    public function store_creates_team_and_returns_201(): void
    {
        $response = $this->postJson('/api/v1/teams', [
            'name' => 'Team Alpha',
            'role' => 'PRIMARY',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Team Alpha')
            ->assertJsonPath('data.role', 'PRIMARY');

        $this->assertDatabaseHas('teams', ['name' => 'Team Alpha']);
    }

    #[Test]
    public function store_fails_validation_when_name_missing(): void
    {
        $this->postJson('/api/v1/teams', ['role' => 'PRIMARY'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function store_fails_validation_when_role_invalid(): void
    {
        $this->postJson('/api/v1/teams', ['name' => 'Team X', 'role' => 'INVALID'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    // =========================================================================
    // PUT /api/v1/teams/{team}
    // =========================================================================

    #[Test]
    public function update_modifies_team(): void
    {
        $team = Team::factory()->active()->create(['name' => 'Old Name']);

        $response = $this->putJson("/api/v1/teams/{$team->id}", [
            'name' => 'New Name',
            'status' => 'paused',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'New Name')
            ->assertJsonPath('data.status', 'paused');

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'New Name']);
    }

    #[Test]
    public function update_fails_validation_when_status_invalid(): void
    {
        $team = Team::factory()->create();

        $this->putJson("/api/v1/teams/{$team->id}", ['status' => 'bogus'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    // =========================================================================
    // DELETE /api/v1/teams/{team}
    // =========================================================================

    #[Test]
    public function destroy_deletes_team_and_returns_204(): void
    {
        $team = Team::factory()->create();

        $this->deleteJson("/api/v1/teams/{$team->id}")->assertNoContent();

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    // =========================================================================
    // POST /api/v1/teams/{team}/accounts
    // =========================================================================

    #[Test]
    public function assign_accounts_sets_team_id_on_accounts(): void
    {
        $team = Team::factory()->create();
        $accounts = Account::factory()->count(2)->create(['team_id' => null]);

        $response = $this->postJson("/api/v1/teams/{$team->id}/accounts", [
            'account_ids' => $accounts->pluck('id')->all(),
        ]);

        $response->assertNoContent();

        foreach ($accounts as $account) {
            $this->assertDatabaseHas('accounts', ['id' => $account->id, 'team_id' => $team->id]);
        }
    }

    #[Test]
    public function assign_accounts_fails_validation_when_account_ids_missing(): void
    {
        $team = Team::factory()->create();

        $this->postJson("/api/v1/teams/{$team->id}/accounts", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['account_ids']);
    }

    #[Test]
    public function assign_accounts_fails_validation_when_account_id_does_not_exist(): void
    {
        $team = Team::factory()->create();

        $this->postJson("/api/v1/teams/{$team->id}/accounts", [
            'account_ids' => ['00000000-0000-0000-0000-000000000000'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['account_ids.0']);
    }

    // =========================================================================
    // GET /api/v1/teams/{team}/stats
    // =========================================================================

    #[Test]
    public function stats_returns_team_statistics(): void
    {
        $team = Team::factory()->create();
        Account::factory()->count(2)->create([
            'team_id' => $team->id,
            'status' => AccountStatus::Active,
            'commission_pct' => '2.00',
        ]);
        Account::factory()->paused()->create(['team_id' => $team->id]);

        $response = $this->getJson("/api/v1/teams/{$team->id}/stats");

        $response->assertOk()
            ->assertJsonPath('account_count', 3)
            ->assertJsonPath('active_account_count', 2)
            ->assertJsonPath('total_commission_pct', 4);
    }
}
