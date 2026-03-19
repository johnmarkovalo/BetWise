<?php

namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Enums\MatchupSide;
use App\Enums\MatchupStatus;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Matchup;
use App\Models\Team;
use App\Services\MatchmakingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class MatchmakingServiceTest extends TestCase
{
    use RefreshDatabase;

    private MatchmakingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchmakingService;
    }

    // =========================================================================
    // createMatchup
    // =========================================================================

    #[Test]
    public function create_matchup_persists_matchup_and_pivot_rows(): void
    {
        $primary = Team::factory()->primary()->create();
        $counter = Team::factory()->counter()->create();

        $matchup = $this->service->createMatchup($primary, $counter, 'evolution');

        $this->assertInstanceOf(Matchup::class, $matchup);
        $this->assertDatabaseHas('matchups', [
            'id' => $matchup->id,
            'provider' => 'evolution',
            'status' => MatchupStatus::Active->value,
        ]);
        $this->assertDatabaseHas('matchup_teams', [
            'matchup_id' => $matchup->id,
            'team_id' => $primary->id,
            'side' => MatchupSide::Banker->value,
        ]);
        $this->assertDatabaseHas('matchup_teams', [
            'matchup_id' => $matchup->id,
            'team_id' => $counter->id,
            'side' => MatchupSide::Player->value,
        ]);
    }

    #[Test]
    public function create_matchup_uses_provided_table_id(): void
    {
        $primary = Team::factory()->primary()->create();
        $counter = Team::factory()->counter()->create();

        $matchup = $this->service->createMatchup($primary, $counter, 'evolution', 'CUSTOM-TABLE-99');

        $this->assertDatabaseHas('matchups', ['id' => $matchup->id, 'table_id' => 'CUSTOM-TABLE-99']);
    }

    #[Test]
    public function create_matchup_auto_selects_table_id_when_null(): void
    {
        $primary = Team::factory()->primary()->create();
        $counter = Team::factory()->counter()->create();

        $matchup = $this->service->createMatchup($primary, $counter, 'evolution', null);

        $this->assertDatabaseHas('matchups', ['id' => $matchup->id, 'table_id' => 'EVO-BACCARAT-01']);
    }

    #[Test]
    public function create_matchup_uses_fallback_table_for_unknown_provider(): void
    {
        $primary = Team::factory()->primary()->create();
        $counter = Team::factory()->counter()->create();

        $matchup = $this->service->createMatchup($primary, $counter, 'playtech', null);

        $this->assertDatabaseHas('matchups', ['id' => $matchup->id, 'table_id' => 'TABLE-BACCARAT-01']);
    }

    #[Test]
    public function create_matchup_writes_audit_log_with_manual_mode(): void
    {
        $primary = Team::factory()->primary()->create();
        $counter = Team::factory()->counter()->create();

        $matchup = $this->service->createMatchup($primary, $counter, 'evolution');

        $log = AuditLog::query()
            ->where('action', 'matchup.created')
            ->where('entity_id', $matchup->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('manual', $log->payload['mode']);
        $this->assertEquals($primary->id, $log->payload['primary_team_id']);
        $this->assertEquals($counter->id, $log->payload['counter_team_id']);
    }

    #[Test]
    public function create_matchup_throws_when_primary_team_has_wrong_role(): void
    {
        $wrongRole = Team::factory()->counter()->create();
        $counter = Team::factory()->counter()->create();

        $this->expectException(LogicException::class);

        $this->service->createMatchup($wrongRole, $counter, 'evolution');
    }

    #[Test]
    public function create_matchup_throws_when_counter_team_has_wrong_role(): void
    {
        $primary = Team::factory()->primary()->create();
        $wrongRole = Team::factory()->primary()->create();

        $this->expectException(LogicException::class);

        $this->service->createMatchup($primary, $wrongRole, 'evolution');
    }

    // =========================================================================
    // findBalancedMatchup
    // =========================================================================

    #[Test]
    public function find_balanced_matchup_creates_matchup_with_auto_mode(): void
    {
        $this->seedBalancedTeams('evolution');

        $matchup = $this->service->findBalancedMatchup('evolution');

        $this->assertInstanceOf(Matchup::class, $matchup);
        $this->assertDatabaseHas('matchups', [
            'id' => $matchup->id,
            'provider' => 'evolution',
            'status' => MatchupStatus::Active->value,
        ]);

        $log = AuditLog::query()
            ->where('action', 'matchup.created')
            ->where('entity_id', $matchup->id)
            ->first();

        $this->assertEquals('auto', $log->payload['mode']);
    }

    #[Test]
    public function find_balanced_matchup_picks_pair_with_closest_commission_ratio(): void
    {
        // primary A: commission 2.0 — primary B: commission 5.0
        // counter X: commission 2.1 — counter Y: commission 1.0
        // A+X ratio = 2.0/2.1 ≈ 0.952  (best)
        // A+Y ratio = 1.0/2.0 = 0.500
        // B+X ratio = 2.1/5.0 = 0.420
        // B+Y ratio = 1.0/5.0 = 0.200

        $teamA = Team::factory()->primary()->create();
        Account::factory()->create(['team_id' => $teamA->id, 'status' => AccountStatus::Active, 'provider' => 'evolution', 'commission_pct' => '2.00']);

        $teamB = Team::factory()->primary()->create();
        Account::factory()->create(['team_id' => $teamB->id, 'status' => AccountStatus::Active, 'provider' => 'evolution', 'commission_pct' => '5.00']);

        $teamX = Team::factory()->counter()->create();
        Account::factory()->create(['team_id' => $teamX->id, 'status' => AccountStatus::Active, 'provider' => 'evolution', 'commission_pct' => '2.10']);

        $teamY = Team::factory()->counter()->create();
        Account::factory()->create(['team_id' => $teamY->id, 'status' => AccountStatus::Active, 'provider' => 'evolution', 'commission_pct' => '1.00']);

        $matchup = $this->service->findBalancedMatchup('evolution');

        $this->assertDatabaseHas('matchup_teams', ['matchup_id' => $matchup->id, 'team_id' => $teamA->id]);
        $this->assertDatabaseHas('matchup_teams', ['matchup_id' => $matchup->id, 'team_id' => $teamX->id]);
    }

    #[Test]
    public function find_balanced_matchup_ignores_teams_without_active_accounts_for_provider(): void
    {
        // Only one eligible primary (has evolution accounts); another primary has no evolution accounts
        $eligiblePrimary = Team::factory()->primary()->create();
        Account::factory()->create(['team_id' => $eligiblePrimary->id, 'status' => AccountStatus::Active, 'provider' => 'evolution', 'commission_pct' => '2.00']);

        $ineligiblePrimary = Team::factory()->primary()->create();
        Account::factory()->create(['team_id' => $ineligiblePrimary->id, 'status' => AccountStatus::Active, 'provider' => 'pragmatic', 'commission_pct' => '2.00']);

        $counter = Team::factory()->counter()->create();
        Account::factory()->create(['team_id' => $counter->id, 'status' => AccountStatus::Active, 'provider' => 'evolution', 'commission_pct' => '2.00']);

        $matchup = $this->service->findBalancedMatchup('evolution');

        $this->assertDatabaseHas('matchup_teams', ['matchup_id' => $matchup->id, 'team_id' => $eligiblePrimary->id]);
        $this->assertDatabaseMissing('matchup_teams', ['matchup_id' => $matchup->id, 'team_id' => $ineligiblePrimary->id]);
    }

    #[Test]
    public function find_balanced_matchup_throws_when_no_eligible_primary_teams(): void
    {
        $counter = Team::factory()->counter()->create();
        Account::factory()->create(['team_id' => $counter->id, 'status' => AccountStatus::Active, 'provider' => 'evolution']);

        $this->expectException(RuntimeException::class);

        $this->service->findBalancedMatchup('evolution');
    }

    #[Test]
    public function find_balanced_matchup_throws_when_no_eligible_counter_teams(): void
    {
        $primary = Team::factory()->primary()->create();
        Account::factory()->create(['team_id' => $primary->id, 'status' => AccountStatus::Active, 'provider' => 'evolution']);

        $this->expectException(RuntimeException::class);

        $this->service->findBalancedMatchup('evolution');
    }

    // =========================================================================
    // activateMatchup
    // =========================================================================

    #[Test]
    public function activate_matchup_sets_status_to_active(): void
    {
        $matchup = Matchup::factory()->create(['status' => MatchupStatus::Paused]);

        $result = $this->service->activateMatchup($matchup);

        $this->assertEquals(MatchupStatus::Active, $result->status);
        $this->assertDatabaseHas('matchups', ['id' => $matchup->id, 'status' => MatchupStatus::Active->value]);
    }

    #[Test]
    public function activate_matchup_writes_audit_log(): void
    {
        $matchup = Matchup::factory()->create(['status' => MatchupStatus::Paused]);

        $this->service->activateMatchup($matchup);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'matchup.activated',
            'entity_type' => 'Matchup',
            'entity_id' => $matchup->id,
        ]);
    }

    #[Test]
    public function activate_matchup_throws_when_completed(): void
    {
        $matchup = Matchup::factory()->completed()->create();

        $this->expectException(LogicException::class);

        $this->service->activateMatchup($matchup);
    }

    // =========================================================================
    // deactivateMatchup
    // =========================================================================

    #[Test]
    public function deactivate_matchup_sets_status_to_paused(): void
    {
        $matchup = Matchup::factory()->create(['status' => MatchupStatus::Active]);

        $result = $this->service->deactivateMatchup($matchup);

        $this->assertEquals(MatchupStatus::Paused, $result->status);
        $this->assertDatabaseHas('matchups', ['id' => $matchup->id, 'status' => MatchupStatus::Paused->value]);
    }

    #[Test]
    public function deactivate_matchup_writes_audit_log(): void
    {
        $matchup = Matchup::factory()->create(['status' => MatchupStatus::Active]);

        $this->service->deactivateMatchup($matchup);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'matchup.deactivated',
            'entity_type' => 'Matchup',
            'entity_id' => $matchup->id,
        ]);
    }

    #[Test]
    public function deactivate_matchup_throws_when_completed(): void
    {
        $matchup = Matchup::factory()->completed()->create();

        $this->expectException(LogicException::class);

        $this->service->deactivateMatchup($matchup);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function seedBalancedTeams(string $provider): void
    {
        $primary = Team::factory()->primary()->create();
        Account::factory()->create([
            'team_id' => $primary->id,
            'status' => AccountStatus::Active,
            'provider' => $provider,
            'commission_pct' => '2.50',
        ]);

        $counter = Team::factory()->counter()->create();
        Account::factory()->create([
            'team_id' => $counter->id,
            'status' => AccountStatus::Active,
            'provider' => $provider,
            'commission_pct' => '2.50',
        ]);
    }
}
