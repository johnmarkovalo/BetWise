<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\AllocationOutcome;
use App\Enums\DeviceStatus;
use App\Enums\MatchupSide;
use App\Enums\MatchupStatus;
use App\Enums\RoundStatus;
use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use App\Models\Account;
use App\Models\Allocation;
use App\Models\Capital;
use App\Models\Device;
use App\Models\Matchup;
use App\Models\Round;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Phase1FoundationTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Migrations — all expected tables exist with correct columns
    // =========================================================================

    #[Test]
    public function all_core_tables_exist(): void
    {
        $tables = [
            'teams',
            'accounts',
            'capitals',
            'devices',
            'matchups',
            'matchup_teams',
            'rounds',
            'allocations',
            'device_ips',
            'ip_conflict_rules',
            'ip_usage_logs',
            'proxy_pool',
            'audit_logs',
            'telemetry',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table [{$table}] does not exist.");
        }
    }

    #[Test]
    public function teams_table_has_correct_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('teams', ['id', 'name', 'role', 'status', 'created_at', 'updated_at']));
    }

    #[Test]
    public function accounts_table_has_correct_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('accounts', [
            'id', 'team_id', 'provider', 'commission_pct', 'min_balance_threshold', 'status',
            'created_at', 'updated_at',
        ]));
    }

    #[Test]
    public function capitals_table_has_correct_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('capitals', ['account_id', 'balance', 'locked', 'updated_at']));
    }

    #[Test]
    public function rounds_table_has_correct_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('rounds', [
            'id', 'matchup_id', 'execute_at', 'status', 'seed', 'total_capital', 'created_at', 'updated_at',
        ]));
    }

    #[Test]
    public function allocations_table_has_correct_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('allocations', [
            'id', 'round_id', 'account_id', 'side', 'amount', 'outcome', 'payout', 'executed_at',
        ]));
    }

    // =========================================================================
    // Factories — models can be created without errors
    // =========================================================================

    #[Test]
    public function team_factory_creates_a_team(): void
    {
        $team = Team::factory()->create();

        $this->assertDatabaseHas('teams', ['id' => $team->id]);
        $this->assertInstanceOf(TeamStatus::class, $team->status);
        $this->assertInstanceOf(TeamRole::class, $team->role);
    }

    #[Test]
    public function team_factory_states_work(): void
    {
        $primary = Team::factory()->primary()->create();
        $counter = Team::factory()->counter()->create();
        $inactive = Team::factory()->inactive()->create();

        $this->assertEquals(TeamRole::Primary, $primary->role);
        $this->assertEquals(TeamRole::Counter, $counter->role);
        $this->assertEquals(TeamStatus::Inactive, $inactive->status);
    }

    #[Test]
    public function account_factory_creates_an_account(): void
    {
        $account = Account::factory()->create();

        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
        $this->assertInstanceOf(AccountStatus::class, $account->status);
        $this->assertNotNull($account->team_id);
    }

    #[Test]
    public function account_factory_states_work(): void
    {
        $inactive = Account::factory()->inactive()->create();
        $paused = Account::factory()->paused()->create();

        $this->assertEquals(AccountStatus::Inactive, $inactive->status);
        $this->assertEquals(AccountStatus::Paused, $paused->status);
    }

    #[Test]
    public function capital_factory_creates_a_capital(): void
    {
        $capital = Capital::factory()->create();

        $this->assertDatabaseHas('capitals', ['account_id' => $capital->account_id]);
        $this->assertGreaterThanOrEqual(0.0, (float) $capital->balance);
    }

    #[Test]
    public function capital_factory_empty_state_works(): void
    {
        $capital = Capital::factory()->empty()->create();

        $this->assertEquals('0.00', $capital->balance);
        $this->assertEquals('0.00', $capital->locked);
    }

    #[Test]
    public function matchup_factory_creates_a_matchup(): void
    {
        $matchup = Matchup::factory()->create();

        $this->assertDatabaseHas('matchups', ['id' => $matchup->id]);
        $this->assertInstanceOf(MatchupStatus::class, $matchup->status);
    }

    #[Test]
    public function matchup_factory_states_work(): void
    {
        $locked = Matchup::factory()->locked()->create();
        $completed = Matchup::factory()->completed()->create();

        $this->assertNotNull($locked->locked_at);
        $this->assertEquals(MatchupStatus::Completed, $completed->status);
    }

    #[Test]
    public function round_factory_creates_a_round(): void
    {
        $round = Round::factory()->create();

        $this->assertDatabaseHas('rounds', ['id' => $round->id]);
        $this->assertInstanceOf(RoundStatus::class, $round->status);
    }

    #[Test]
    public function round_factory_states_work(): void
    {
        $prepared = Round::factory()->prepared()->create();
        $executing = Round::factory()->executing()->create();
        $completed = Round::factory()->completed()->create();
        $aborted = Round::factory()->aborted()->create();

        $this->assertEquals(RoundStatus::Prepared, $prepared->status);
        $this->assertEquals(RoundStatus::Executing, $executing->status);
        $this->assertEquals(RoundStatus::Completed, $completed->status);
        $this->assertEquals(RoundStatus::Aborted, $aborted->status);
    }

    #[Test]
    public function allocation_factory_creates_an_allocation(): void
    {
        $allocation = Allocation::factory()->create();

        $this->assertDatabaseHas('allocations', ['id' => $allocation->id]);
        $this->assertInstanceOf(MatchupSide::class, $allocation->side);
        $this->assertNull($allocation->outcome);
    }

    #[Test]
    public function allocation_factory_settled_state_works(): void
    {
        $allocation = Allocation::factory()->settled()->create();

        $this->assertNotNull($allocation->outcome);
        $this->assertInstanceOf(AllocationOutcome::class, $allocation->outcome);
        $this->assertNotNull($allocation->executed_at);
    }

    #[Test]
    public function device_factory_creates_a_device(): void
    {
        $device = Device::factory()->create();

        $this->assertDatabaseHas('devices', ['id' => $device->id]);
        $this->assertInstanceOf(DeviceStatus::class, $device->status);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    #[Test]
    public function team_has_many_accounts(): void
    {
        $team = Team::factory()->create();
        Account::factory()->count(3)->create(['team_id' => $team->id]);

        $this->assertCount(3, $team->accounts);
        $this->assertInstanceOf(Account::class, $team->accounts->first());
    }

    #[Test]
    public function account_belongs_to_team(): void
    {
        $team = Team::factory()->create();
        $account = Account::factory()->create(['team_id' => $team->id]);

        $this->assertEquals($team->id, $account->team->id);
    }

    #[Test]
    public function account_has_one_capital(): void
    {
        $account = Account::factory()->create();
        $capital = Capital::factory()->create(['account_id' => $account->id]);

        $this->assertEquals($capital->account_id, $account->capital->account_id);
    }

    #[Test]
    public function capital_belongs_to_account(): void
    {
        $capital = Capital::factory()->create();

        $this->assertInstanceOf(Account::class, $capital->account);
    }

    #[Test]
    public function matchup_has_many_rounds(): void
    {
        $matchup = Matchup::factory()->create();
        Round::factory()->count(2)->create(['matchup_id' => $matchup->id]);

        $this->assertCount(2, $matchup->rounds);
        $this->assertInstanceOf(Round::class, $matchup->rounds->first());
    }

    #[Test]
    public function round_belongs_to_matchup(): void
    {
        $round = Round::factory()->create();

        $this->assertInstanceOf(Matchup::class, $round->matchup);
    }

    #[Test]
    public function round_has_many_allocations(): void
    {
        $round = Round::factory()->create();
        Allocation::factory()->count(4)->create(['round_id' => $round->id]);

        $this->assertCount(4, $round->allocations);
    }

    #[Test]
    public function allocation_belongs_to_round_and_account(): void
    {
        $allocation = Allocation::factory()->create();

        $this->assertInstanceOf(Round::class, $allocation->round);
        $this->assertInstanceOf(Account::class, $allocation->account);
    }

    #[Test]
    public function team_belongs_to_many_matchups(): void
    {
        $team = Team::factory()->create();
        $matchup = Matchup::factory()->create();

        $matchup->teams()->attach($team->id, ['side' => MatchupSide::Banker->value]);

        $this->assertCount(1, $team->matchups);
        $this->assertInstanceOf(Matchup::class, $team->matchups->first());
    }

    #[Test]
    public function device_belongs_to_account(): void
    {
        $device = Device::factory()->create();

        $this->assertInstanceOf(Account::class, $device->account);
    }

    #[Test]
    public function account_has_many_devices(): void
    {
        $account = Account::factory()->create();
        Device::factory()->count(2)->create(['account_id' => $account->id]);

        $this->assertCount(2, $account->devices);
    }

    // =========================================================================
    // Model scopes
    // =========================================================================

    #[Test]
    public function team_active_scope_filters_correctly(): void
    {
        Team::factory()->count(2)->create(['status' => TeamStatus::Active]);
        Team::factory()->inactive()->create();

        $this->assertCount(2, Team::active()->get());
    }

    #[Test]
    public function team_primary_and_counter_scopes_work(): void
    {
        Team::factory()->primary()->count(2)->create();
        Team::factory()->counter()->create();

        $this->assertCount(2, Team::primary()->get());
        $this->assertCount(1, Team::counter()->get());
    }

    #[Test]
    public function account_active_scope_filters_correctly(): void
    {
        Account::factory()->count(3)->create(['status' => AccountStatus::Active]);
        Account::factory()->inactive()->create();
        Account::factory()->paused()->create();

        $this->assertCount(3, Account::active()->get());
    }

    #[Test]
    public function round_scopes_filter_by_status(): void
    {
        Round::factory()->prepared()->create();
        Round::factory()->executing()->create();
        Round::factory()->completed()->count(2)->create();

        $this->assertCount(1, Round::prepared()->get());
        $this->assertCount(1, Round::executing()->get());
        $this->assertCount(2, Round::completed()->get());
    }

    #[Test]
    public function round_pending_scope_includes_preparing_and_prepared(): void
    {
        Round::factory()->create(['status' => RoundStatus::Preparing]);
        Round::factory()->prepared()->create();
        Round::factory()->executing()->create();

        $this->assertCount(2, Round::pending()->get());
    }

    #[Test]
    public function matchup_active_scope_filters_correctly(): void
    {
        Matchup::factory()->count(2)->create(['status' => MatchupStatus::Active]);
        Matchup::factory()->completed()->create();

        $this->assertCount(2, Matchup::active()->get());
    }

    #[Test]
    public function allocation_settled_scope_filters_correctly(): void
    {
        Allocation::factory()->settled()->count(2)->create();
        Allocation::factory()->create(['outcome' => null]);

        $this->assertCount(2, Allocation::settled()->get());
    }

    // =========================================================================
    // Capital computed attribute
    // =========================================================================

    #[Test]
    public function capital_available_attribute_returns_balance_minus_locked(): void
    {
        $capital = Capital::factory()->create(['balance' => '1000.00', 'locked' => '250.00']);

        $this->assertEquals('750.00', $capital->available);
    }

    // =========================================================================
    // Enum coverage
    // =========================================================================

    #[Test]
    public function all_enum_cases_are_defined(): void
    {
        $this->assertCount(2, TeamRole::cases());
        $this->assertCount(3, TeamStatus::cases());
        $this->assertCount(3, AccountStatus::cases());
        $this->assertCount(3, DeviceStatus::cases());
        $this->assertCount(3, MatchupStatus::cases());
        $this->assertCount(5, RoundStatus::cases());
        $this->assertCount(3, MatchupSide::cases());
        $this->assertCount(4, AllocationOutcome::cases());
    }
}
