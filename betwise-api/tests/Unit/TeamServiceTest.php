<?php

namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Team;
use App\Services\TeamService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TeamServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeamService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TeamService;
    }

    // =========================================================================
    // createTeam
    // =========================================================================

    #[Test]
    public function create_team_persists_team_to_database(): void
    {
        $team = $this->service->createTeam([
            'name' => 'Alpha Team',
            'role' => TeamRole::Primary->value,
        ]);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'name' => 'Alpha Team',
            'role' => TeamRole::Primary->value,
        ]);
    }

    #[Test]
    public function create_team_writes_audit_log(): void
    {
        $team = $this->service->createTeam([
            'name' => 'Beta Team',
            'role' => TeamRole::Counter->value,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.created',
            'entity_type' => 'Team',
            'entity_id' => $team->id,
        ]);
    }

    #[Test]
    public function create_team_defaults_status_to_active(): void
    {
        $team = $this->service->createTeam([
            'name' => 'Gamma Team',
            'role' => TeamRole::Primary->value,
        ]);

        $this->assertEquals(TeamStatus::Active, $team->status);
    }

    // =========================================================================
    // updateTeam
    // =========================================================================

    #[Test]
    public function update_team_persists_changes(): void
    {
        $team = Team::factory()->create(['name' => 'Old Name']);

        $updated = $this->service->updateTeam($team, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'New Name']);
    }

    #[Test]
    public function update_team_writes_audit_log_with_before_and_after(): void
    {
        $team = Team::factory()->create(['name' => 'Before']);

        $this->service->updateTeam($team, ['name' => 'After']);

        $log = AuditLog::query()
            ->where('action', 'team.updated')
            ->where('entity_id', $team->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Before', $log->payload['before']['name']);
        $this->assertEquals('After', $log->payload['after']['name']);
    }

    #[Test]
    public function update_team_can_change_status(): void
    {
        $team = Team::factory()->create(['status' => TeamStatus::Active]);

        $updated = $this->service->updateTeam($team, ['status' => TeamStatus::Inactive->value]);

        $this->assertEquals(TeamStatus::Inactive, $updated->status);
    }

    // =========================================================================
    // assignAccounts
    // =========================================================================

    #[Test]
    public function assign_accounts_updates_team_id_on_accounts(): void
    {
        $team = Team::factory()->create();
        $accounts = Account::factory()->count(3)->create(['team_id' => null]);
        $ids = $accounts->pluck('id')->all();

        $this->service->assignAccounts($team, $ids);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('accounts', ['id' => $id, 'team_id' => $team->id]);
        }
    }

    #[Test]
    public function assign_accounts_writes_audit_log(): void
    {
        $team = Team::factory()->create();
        $accounts = Account::factory()->count(2)->create(['team_id' => null]);
        $ids = $accounts->pluck('id')->all();

        $this->service->assignAccounts($team, $ids);

        $log = AuditLog::query()
            ->where('action', 'team.accounts_assigned')
            ->where('entity_id', $team->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEqualsCanonicalizing($ids, $log->payload['account_ids']);
    }

    // =========================================================================
    // removeAccount
    // =========================================================================

    #[Test]
    public function remove_account_sets_team_id_to_null(): void
    {
        $team = Team::factory()->create();
        $account = Account::factory()->create(['team_id' => $team->id]);

        $this->service->removeAccount($team, $account);

        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'team_id' => null]);
    }

    #[Test]
    public function remove_account_writes_audit_log(): void
    {
        $team = Team::factory()->create();
        $account = Account::factory()->create(['team_id' => $team->id]);

        $this->service->removeAccount($team, $account);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'team.account_removed',
            'entity_type' => 'Team',
            'entity_id' => $team->id,
        ]);
    }

    #[Test]
    public function remove_account_throws_when_account_belongs_to_different_team(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $account = Account::factory()->create(['team_id' => $teamB->id]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->removeAccount($teamA, $account);
    }

    // =========================================================================
    // validateTeamComposition
    // =========================================================================

    #[Test]
    public function validate_team_composition_returns_empty_for_valid_team(): void
    {
        $team = Team::factory()->active()->create();
        Account::factory()->create([
            'team_id' => $team->id,
            'status' => AccountStatus::Active,
            'provider' => 'evolution',
        ]);

        $errors = $this->service->validateTeamComposition($team);

        $this->assertEmpty($errors);
    }

    #[Test]
    public function validate_team_composition_errors_when_team_is_inactive(): void
    {
        $team = Team::factory()->inactive()->create();
        Account::factory()->create(['team_id' => $team->id, 'status' => AccountStatus::Active]);

        $errors = $this->service->validateTeamComposition($team);

        $this->assertContains('Team is not active.', $errors);
    }

    #[Test]
    public function validate_team_composition_errors_when_no_active_accounts(): void
    {
        $team = Team::factory()->active()->create();
        Account::factory()->paused()->create(['team_id' => $team->id]);

        $errors = $this->service->validateTeamComposition($team);

        $this->assertContains('Team has no active accounts.', $errors);
    }

    #[Test]
    public function validate_team_composition_errors_when_duplicate_provider(): void
    {
        $team = Team::factory()->active()->create();
        Account::factory()->count(2)->create([
            'team_id' => $team->id,
            'status' => AccountStatus::Active,
            'provider' => 'evolution',
        ]);

        $errors = $this->service->validateTeamComposition($team);

        $this->assertContains('Team has multiple active accounts on the same provider.', $errors);
    }

    #[Test]
    public function validate_team_composition_ignores_inactive_duplicate_provider(): void
    {
        $team = Team::factory()->active()->create();
        Account::factory()->create([
            'team_id' => $team->id,
            'status' => AccountStatus::Active,
            'provider' => 'evolution',
        ]);
        Account::factory()->inactive()->create([
            'team_id' => $team->id,
            'provider' => 'evolution',
        ]);

        $errors = $this->service->validateTeamComposition($team);

        $this->assertEmpty($errors);
    }

    // =========================================================================
    // getTeamStats
    // =========================================================================

    #[Test]
    public function get_team_stats_returns_correct_counts(): void
    {
        $team = Team::factory()->create();
        Account::factory()->count(2)->create(['team_id' => $team->id, 'status' => AccountStatus::Active]);
        Account::factory()->paused()->create(['team_id' => $team->id]);

        $stats = $this->service->getTeamStats($team);

        $this->assertEquals(3, $stats['account_count']);
        $this->assertEquals(2, $stats['active_account_count']);
    }

    #[Test]
    public function get_team_stats_returns_correct_commission_aggregates(): void
    {
        $team = Team::factory()->create();
        Account::factory()->create(['team_id' => $team->id, 'status' => AccountStatus::Active, 'commission_pct' => '1.00']);
        Account::factory()->create(['team_id' => $team->id, 'status' => AccountStatus::Active, 'commission_pct' => '3.00']);

        $stats = $this->service->getTeamStats($team);

        $this->assertEquals(4.0, $stats['total_commission_pct']);
        $this->assertEquals(2.0, $stats['average_commission_pct']);
        $this->assertEquals(1.0, $stats['min_commission_pct']);
        $this->assertEquals(3.0, $stats['max_commission_pct']);
    }

    #[Test]
    public function get_team_stats_returns_zeros_when_no_active_accounts(): void
    {
        $team = Team::factory()->create();
        Account::factory()->paused()->create(['team_id' => $team->id]);

        $stats = $this->service->getTeamStats($team);

        $this->assertEquals(0.0, $stats['total_commission_pct']);
        $this->assertEquals(0.0, $stats['average_commission_pct']);
        $this->assertEquals(0.0, $stats['min_commission_pct']);
        $this->assertEquals(0.0, $stats['max_commission_pct']);
    }

    #[Test]
    public function get_team_stats_excludes_inactive_accounts_from_commission(): void
    {
        $team = Team::factory()->create();
        Account::factory()->create(['team_id' => $team->id, 'status' => AccountStatus::Active, 'commission_pct' => '2.00']);
        Account::factory()->inactive()->create(['team_id' => $team->id, 'commission_pct' => '10.00']);

        $stats = $this->service->getTeamStats($team);

        $this->assertEquals(2.0, $stats['total_commission_pct']);
        $this->assertEquals(2.0, $stats['max_commission_pct']);
    }
}
