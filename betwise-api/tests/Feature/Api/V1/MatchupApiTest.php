<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AccountStatus;
use App\Enums\MatchupSide;
use App\Enums\MatchupStatus;
use App\Models\Account;
use App\Models\Matchup;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MatchupApiTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // GET /api/v1/matchups
    // =========================================================================

    #[Test]
    public function index_returns_paginated_matchups(): void
    {
        $primaryTeam = Team::factory()->primary()->create();
        $counterTeam = Team::factory()->counter()->create();
        $matchups = Matchup::factory()->count(3)->create();

        foreach ($matchups as $matchup) {
            $matchup->teams()->attach($primaryTeam->id, ['side' => MatchupSide::Banker->value]);
            $matchup->teams()->attach($counterTeam->id, ['side' => MatchupSide::Player->value]);
        }

        $response = $this->getJson('/api/v1/matchups');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function index_filters_by_status(): void
    {
        Matchup::factory()->count(2)->create(['status' => MatchupStatus::Active]);
        Matchup::factory()->create(['status' => MatchupStatus::Paused]);

        $response = $this->getJson('/api/v1/matchups?status=active');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function index_filters_by_provider(): void
    {
        Matchup::factory()->count(2)->create(['provider' => 'evolution']);
        Matchup::factory()->create(['provider' => 'pragmatic']);

        $response = $this->getJson('/api/v1/matchups?provider=evolution');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    // =========================================================================
    // GET /api/v1/matchups/{matchup}
    // =========================================================================

    #[Test]
    public function show_returns_matchup_with_teams(): void
    {
        $matchup = Matchup::factory()->create(['provider' => 'evolution']);
        $primaryTeam = Team::factory()->primary()->create();
        $counterTeam = Team::factory()->counter()->create();
        $matchup->teams()->attach($primaryTeam->id, ['side' => MatchupSide::Banker->value]);
        $matchup->teams()->attach($counterTeam->id, ['side' => MatchupSide::Player->value]);

        $response = $this->getJson("/api/v1/matchups/{$matchup->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $matchup->id)
            ->assertJsonPath('data.provider', 'evolution')
            ->assertJsonCount(2, 'data.teams');
    }

    // =========================================================================
    // POST /api/v1/matchups
    // =========================================================================

    #[Test]
    public function store_creates_matchup_manually_and_returns_201(): void
    {
        $primaryTeam = Team::factory()->primary()->create();
        $counterTeam = Team::factory()->counter()->create();

        $response = $this->postJson('/api/v1/matchups', [
            'provider' => 'evolution',
            'primary_team_id' => $primaryTeam->id,
            'counter_team_id' => $counterTeam->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.provider', 'evolution')
            ->assertJsonCount(2, 'data.teams');

        $this->assertDatabaseHas('matchups', ['provider' => 'evolution']);
    }

    #[Test]
    public function store_uses_provided_table_id(): void
    {
        $primaryTeam = Team::factory()->primary()->create();
        $counterTeam = Team::factory()->counter()->create();

        $response = $this->postJson('/api/v1/matchups', [
            'provider' => 'evolution',
            'table_id' => 'EVO-TABLE-99',
            'primary_team_id' => $primaryTeam->id,
            'counter_team_id' => $counterTeam->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.table_id', 'EVO-TABLE-99');
    }

    #[Test]
    public function store_fails_validation_when_provider_missing(): void
    {
        $primaryTeam = Team::factory()->primary()->create();
        $counterTeam = Team::factory()->counter()->create();

        $this->postJson('/api/v1/matchups', [
            'primary_team_id' => $primaryTeam->id,
            'counter_team_id' => $counterTeam->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    #[Test]
    public function store_fails_validation_when_team_id_does_not_exist(): void
    {
        $primaryTeam = Team::factory()->primary()->create();

        $this->postJson('/api/v1/matchups', [
            'provider' => 'evolution',
            'primary_team_id' => $primaryTeam->id,
            'counter_team_id' => '00000000-0000-0000-0000-000000000000',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['counter_team_id']);
    }

    // =========================================================================
    // POST /api/v1/matchups/auto-generate
    // =========================================================================

    #[Test]
    public function auto_generate_creates_balanced_matchup_and_returns_201(): void
    {
        $primaryTeam = Team::factory()->primary()->active()->create();
        $counterTeam = Team::factory()->counter()->active()->create();

        Account::factory()->create([
            'team_id' => $primaryTeam->id,
            'provider' => 'evolution',
            'status' => AccountStatus::Active,
            'commission_pct' => '3.00',
        ]);
        Account::factory()->create([
            'team_id' => $counterTeam->id,
            'provider' => 'evolution',
            'status' => AccountStatus::Active,
            'commission_pct' => '3.00',
        ]);

        $response = $this->postJson('/api/v1/matchups/auto-generate', [
            'provider' => 'evolution',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.provider', 'evolution')
            ->assertJsonCount(2, 'data.teams');
    }

    #[Test]
    public function auto_generate_fails_validation_when_provider_missing(): void
    {
        $this->postJson('/api/v1/matchups/auto-generate', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    // =========================================================================
    // PUT /api/v1/matchups/{matchup}
    // =========================================================================

    #[Test]
    public function update_activates_a_paused_matchup(): void
    {
        $matchup = Matchup::factory()->create(['status' => MatchupStatus::Paused]);

        $response = $this->putJson("/api/v1/matchups/{$matchup->id}", ['status' => 'active']);

        $response->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertDatabaseHas('matchups', ['id' => $matchup->id, 'status' => 'active']);
    }

    #[Test]
    public function update_deactivates_an_active_matchup(): void
    {
        $matchup = Matchup::factory()->create(['status' => MatchupStatus::Active]);

        $response = $this->putJson("/api/v1/matchups/{$matchup->id}", ['status' => 'paused']);

        $response->assertOk()->assertJsonPath('data.status', 'paused');
        $this->assertDatabaseHas('matchups', ['id' => $matchup->id, 'status' => 'paused']);
    }

    #[Test]
    public function update_fails_validation_when_status_is_completed(): void
    {
        $matchup = Matchup::factory()->create();

        $this->putJson("/api/v1/matchups/{$matchup->id}", ['status' => 'completed'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    #[Test]
    public function update_throws_when_matchup_is_completed(): void
    {
        $matchup = Matchup::factory()->completed()->create();

        $this->withoutExceptionHandling()
            ->expectException(LogicException::class);

        $this->putJson("/api/v1/matchups/{$matchup->id}", ['status' => 'active']);
    }
}
