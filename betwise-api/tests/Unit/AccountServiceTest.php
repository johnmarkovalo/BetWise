<?php

namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Events\AccountLowBalance;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Capital;
use App\Models\Team;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountService;
    }

    // =========================================================================
    // createAccount
    // =========================================================================

    #[Test]
    public function create_account_persists_account_and_capital(): void
    {
        $team = Team::factory()->create();

        $account = $this->service->createAccount([
            'provider' => 'evolution',
            'commission_pct' => 3.5,
            'team_id' => $team->id,
            'initial_balance' => 1000.00,
        ]);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'provider' => 'evolution',
            'status' => AccountStatus::Active->value,
        ]);
        $this->assertDatabaseHas('capitals', [
            'account_id' => $account->id,
            'balance' => '1000.00',
            'locked' => '0.00',
        ]);
    }

    #[Test]
    public function create_account_defaults_balance_to_zero(): void
    {
        $account = $this->service->createAccount([
            'provider' => 'pragmatic',
            'commission_pct' => 2.0,
        ]);

        $this->assertDatabaseHas('capitals', [
            'account_id' => $account->id,
            'balance' => '0.00',
        ]);
    }

    #[Test]
    public function create_account_defaults_min_balance_threshold(): void
    {
        $account = $this->service->createAccount([
            'provider' => 'evolution',
            'commission_pct' => 1.5,
        ]);

        $this->assertEquals('100.00', $account->min_balance_threshold);
    }

    #[Test]
    public function create_account_writes_audit_log(): void
    {
        $account = $this->service->createAccount([
            'provider' => 'evolution',
            'commission_pct' => 3.5,
            'initial_balance' => 500.00,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.created',
            'entity_type' => 'Account',
            'entity_id' => $account->id,
        ]);
    }

    #[Test]
    public function create_account_eager_loads_capital(): void
    {
        $account = $this->service->createAccount([
            'provider' => 'evolution',
            'commission_pct' => 2.0,
            'initial_balance' => 200.00,
        ]);

        $this->assertTrue($account->relationLoaded('capital'));
        $this->assertEquals('200.00', $account->capital->balance);
    }

    // =========================================================================
    // updateBalance — add
    // =========================================================================

    #[Test]
    public function update_balance_add_increases_balance(): void
    {
        $account = Account::factory()->create(['min_balance_threshold' => 50.00]);
        Capital::factory()->create(['account_id' => $account->id, 'balance' => '500.00', 'locked' => '0.00']);

        $capital = $this->service->updateBalance($account, 200.00, 'add', 'Top up');

        $this->assertEquals('700.00', $capital->fresh()->balance);
    }

    #[Test]
    public function update_balance_subtract_decreases_balance(): void
    {
        $account = Account::factory()->create(['min_balance_threshold' => 50.00]);
        Capital::factory()->create(['account_id' => $account->id, 'balance' => '500.00', 'locked' => '0.00']);

        $capital = $this->service->updateBalance($account, 100.00, 'subtract', 'Withdrawal');

        $this->assertEquals('400.00', $capital->fresh()->balance);
    }

    #[Test]
    public function update_balance_writes_audit_log(): void
    {
        $account = Account::factory()->create(['min_balance_threshold' => 50.00]);
        Capital::factory()->create(['account_id' => $account->id, 'balance' => '500.00', 'locked' => '0.00']);

        $this->service->updateBalance($account, 50.00, 'add', 'Test reason');

        $log = AuditLog::query()
            ->where('action', 'account.balance_updated')
            ->where('entity_id', $account->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('add', $log->payload['operation']);
        $this->assertEquals(50.00, $log->payload['amount']);
        $this->assertEquals('Test reason', $log->payload['reason']);
        $this->assertEquals(500.00, $log->payload['before']);
        $this->assertEquals(550.00, $log->payload['after']);
    }

    #[Test]
    public function update_balance_throws_for_zero_amount(): void
    {
        $account = Account::factory()->create();
        Capital::factory()->create(['account_id' => $account->id]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->updateBalance($account, 0.0, 'add', 'Bad call');
    }

    #[Test]
    public function update_balance_throws_for_invalid_operation(): void
    {
        $account = Account::factory()->create();
        Capital::factory()->create(['account_id' => $account->id]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->updateBalance($account, 100.0, 'multiply', 'Bad op');
    }

    // =========================================================================
    // pauseIfLowBalance
    // =========================================================================

    #[Test]
    public function pause_if_low_balance_pauses_account_when_below_threshold(): void
    {
        Event::fake();

        $account = Account::factory()->create([
            'status' => AccountStatus::Active,
            'min_balance_threshold' => 100.00,
        ]);
        $capital = Capital::factory()->create([
            'account_id' => $account->id,
            'balance' => '80.00',
            'locked' => '0.00',
        ]);

        $result = $this->service->pauseIfLowBalance($account, $capital);

        $this->assertTrue($result);
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'status' => AccountStatus::Paused->value,
        ]);
    }

    #[Test]
    public function pause_if_low_balance_fires_event_when_paused(): void
    {
        Event::fake();

        $account = Account::factory()->create([
            'status' => AccountStatus::Active,
            'min_balance_threshold' => 100.00,
        ]);
        $capital = Capital::factory()->create([
            'account_id' => $account->id,
            'balance' => '50.00',
            'locked' => '0.00',
        ]);

        $this->service->pauseIfLowBalance($account, $capital);

        Event::assertDispatched(AccountLowBalance::class, fn ($e) => $e->account->id === $account->id);
    }

    #[Test]
    public function pause_if_low_balance_writes_audit_log(): void
    {
        Event::fake();

        $account = Account::factory()->create([
            'status' => AccountStatus::Active,
            'min_balance_threshold' => 100.00,
        ]);
        $capital = Capital::factory()->create([
            'account_id' => $account->id,
            'balance' => '60.00',
            'locked' => '0.00',
        ]);

        $this->service->pauseIfLowBalance($account, $capital);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'account.auto_paused',
            'entity_type' => 'Account',
            'entity_id' => $account->id,
        ]);
    }

    #[Test]
    public function pause_if_low_balance_does_nothing_when_above_threshold(): void
    {
        Event::fake();

        $account = Account::factory()->create([
            'status' => AccountStatus::Active,
            'min_balance_threshold' => 100.00,
        ]);
        $capital = Capital::factory()->create([
            'account_id' => $account->id,
            'balance' => '500.00',
            'locked' => '0.00',
        ]);

        $result = $this->service->pauseIfLowBalance($account, $capital);

        $this->assertFalse($result);
        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'status' => AccountStatus::Active->value]);
        Event::assertNotDispatched(AccountLowBalance::class);
    }

    #[Test]
    public function pause_if_low_balance_does_nothing_when_already_paused(): void
    {
        Event::fake();

        $account = Account::factory()->paused()->create(['min_balance_threshold' => 100.00]);
        $capital = Capital::factory()->create([
            'account_id' => $account->id,
            'balance' => '10.00',
            'locked' => '0.00',
        ]);

        $result = $this->service->pauseIfLowBalance($account, $capital);

        $this->assertFalse($result);
        Event::assertNotDispatched(AccountLowBalance::class);
    }

    #[Test]
    public function pause_if_low_balance_accounts_for_locked_funds(): void
    {
        Event::fake();

        // balance=200, locked=150 → available=50, threshold=100 → should pause
        $account = Account::factory()->create([
            'status' => AccountStatus::Active,
            'min_balance_threshold' => 100.00,
        ]);
        $capital = Capital::factory()->create([
            'account_id' => $account->id,
            'balance' => '200.00',
            'locked' => '150.00',
        ]);

        $result = $this->service->pauseIfLowBalance($account, $capital);

        $this->assertTrue($result);
        Event::assertDispatched(AccountLowBalance::class);
    }

    #[Test]
    public function update_balance_triggers_auto_pause_when_balance_drops_below_threshold(): void
    {
        Event::fake();

        $account = Account::factory()->create([
            'status' => AccountStatus::Active,
            'min_balance_threshold' => 100.00,
        ]);
        Capital::factory()->create([
            'account_id' => $account->id,
            'balance' => '150.00',
            'locked' => '0.00',
        ]);

        $this->service->updateBalance($account, 100.00, 'subtract', 'Bring below threshold');

        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'status' => AccountStatus::Paused->value]);
        Event::assertDispatched(AccountLowBalance::class);
    }
}
