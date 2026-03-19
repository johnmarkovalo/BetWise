<?php

namespace Tests\Feature\Api\V1;

use App\Enums\AccountStatus;
use App\Models\Account;
use App\Models\Capital;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountApiTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // GET /api/v1/accounts
    // =========================================================================

    #[Test]
    public function index_returns_paginated_accounts(): void
    {
        $accounts = Account::factory()->count(3)->create();
        foreach ($accounts as $account) {
            Capital::factory()->create(['account_id' => $account->id]);
        }

        $response = $this->getJson('/api/v1/accounts');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function index_filters_by_status(): void
    {
        $active1 = Account::factory()->create(['status' => AccountStatus::Active]);
        $active2 = Account::factory()->create(['status' => AccountStatus::Active]);
        $paused = Account::factory()->paused()->create();

        foreach ([$active1, $active2, $paused] as $account) {
            Capital::factory()->create(['account_id' => $account->id]);
        }

        $response = $this->getJson('/api/v1/accounts?status=active');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    #[Test]
    public function index_filters_by_provider(): void
    {
        $evo1 = Account::factory()->create(['provider' => 'evolution']);
        $evo2 = Account::factory()->create(['provider' => 'evolution']);
        $pgp = Account::factory()->create(['provider' => 'pragmatic']);

        foreach ([$evo1, $evo2, $pgp] as $account) {
            Capital::factory()->create(['account_id' => $account->id]);
        }

        $response = $this->getJson('/api/v1/accounts?provider=evolution');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    // =========================================================================
    // GET /api/v1/accounts/{account}
    // =========================================================================

    #[Test]
    public function show_returns_account_with_capital_and_team(): void
    {
        $team = Team::factory()->create();
        $account = Account::factory()->create(['team_id' => $team->id, 'provider' => 'evolution']);
        Capital::factory()->create(['account_id' => $account->id, 'balance' => '1000.00', 'locked' => '0.00']);

        $response = $this->getJson("/api/v1/accounts/{$account->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $account->id)
            ->assertJsonPath('data.provider', 'evolution')
            ->assertJsonPath('data.team_name', $team->name)
            ->assertJsonPath('data.balance', 1000);
    }

    // =========================================================================
    // POST /api/v1/accounts
    // =========================================================================

    #[Test]
    public function store_creates_account_with_capital_and_returns_201(): void
    {
        $team = Team::factory()->create();

        $response = $this->postJson('/api/v1/accounts', [
            'provider' => 'evolution',
            'commission_pct' => 4.5,
            'team_id' => $team->id,
            'initial_balance' => 1000.00,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.provider', 'evolution')
            ->assertJsonPath('data.balance', 1000);

        $this->assertDatabaseHas('accounts', ['provider' => 'evolution', 'team_id' => $team->id]);
        $this->assertDatabaseHas('capitals', ['balance' => '1000.00']);
    }

    #[Test]
    public function store_creates_account_without_team(): void
    {
        $response = $this->postJson('/api/v1/accounts', [
            'provider' => 'pragmatic',
            'commission_pct' => 3.0,
        ]);

        $response->assertCreated()->assertJsonPath('data.team_id', null);
    }

    #[Test]
    public function store_fails_validation_when_provider_missing(): void
    {
        $this->postJson('/api/v1/accounts', ['commission_pct' => 4.5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['provider']);
    }

    #[Test]
    public function store_fails_validation_when_commission_pct_missing(): void
    {
        $this->postJson('/api/v1/accounts', ['provider' => 'evolution'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['commission_pct']);
    }

    #[Test]
    public function store_fails_validation_when_team_id_does_not_exist(): void
    {
        $this->postJson('/api/v1/accounts', [
            'provider' => 'evolution',
            'commission_pct' => 4.5,
            'team_id' => '00000000-0000-0000-0000-000000000000',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['team_id']);
    }

    // =========================================================================
    // PUT /api/v1/accounts/{account}
    // =========================================================================

    #[Test]
    public function update_modifies_account_fields(): void
    {
        $account = Account::factory()->create(['provider' => 'evolution', 'status' => AccountStatus::Active]);
        Capital::factory()->create(['account_id' => $account->id]);

        $response = $this->putJson("/api/v1/accounts/{$account->id}", [
            'provider' => 'pragmatic',
            'status' => 'paused',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.provider', 'pragmatic')
            ->assertJsonPath('data.status', 'paused');

        $this->assertDatabaseHas('accounts', ['id' => $account->id, 'provider' => 'pragmatic']);
    }

    #[Test]
    public function update_fails_validation_when_status_invalid(): void
    {
        $account = Account::factory()->create();
        Capital::factory()->create(['account_id' => $account->id]);

        $this->putJson("/api/v1/accounts/{$account->id}", ['status' => 'unknown'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    // =========================================================================
    // DELETE /api/v1/accounts/{account}
    // =========================================================================

    #[Test]
    public function destroy_deletes_account_and_returns_204(): void
    {
        $account = Account::factory()->create();
        Capital::factory()->create(['account_id' => $account->id]);

        $this->deleteJson("/api/v1/accounts/{$account->id}")->assertNoContent();

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }

    // =========================================================================
    // POST /api/v1/accounts/{account}/balance
    // =========================================================================

    #[Test]
    public function update_balance_adds_to_account_balance(): void
    {
        $account = Account::factory()->create(['min_balance_threshold' => '100.00']);
        Capital::factory()->create(['account_id' => $account->id, 'balance' => '500.00', 'locked' => '0.00']);

        $response = $this->postJson("/api/v1/accounts/{$account->id}/balance", [
            'amount' => 200.00,
            'operation' => 'add',
            'reason' => 'Top-up',
        ]);

        $response->assertOk()->assertJsonPath('data.balance', 700);
    }

    #[Test]
    public function update_balance_subtracts_from_account_balance(): void
    {
        $account = Account::factory()->create(['min_balance_threshold' => '100.00']);
        Capital::factory()->create(['account_id' => $account->id, 'balance' => '500.00', 'locked' => '0.00']);

        $response = $this->postJson("/api/v1/accounts/{$account->id}/balance", [
            'amount' => 100.00,
            'operation' => 'subtract',
            'reason' => 'Withdrawal',
        ]);

        $response->assertOk()->assertJsonPath('data.balance', 400);
    }

    #[Test]
    public function update_balance_triggers_auto_pause_when_balance_drops_below_threshold(): void
    {
        $account = Account::factory()->create([
            'status' => AccountStatus::Active,
            'min_balance_threshold' => '200.00',
        ]);
        Capital::factory()->create(['account_id' => $account->id, 'balance' => '250.00', 'locked' => '0.00']);

        $this->postJson("/api/v1/accounts/{$account->id}/balance", [
            'amount' => 100.00,
            'operation' => 'subtract',
            'reason' => 'Bet placement',
        ])->assertOk();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'status' => AccountStatus::Paused->value,
        ]);
    }

    #[Test]
    public function update_balance_fails_validation_when_amount_is_zero(): void
    {
        $account = Account::factory()->create();
        Capital::factory()->create(['account_id' => $account->id]);

        $this->postJson("/api/v1/accounts/{$account->id}/balance", [
            'amount' => 0,
            'operation' => 'add',
            'reason' => 'Test',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    #[Test]
    public function update_balance_fails_validation_when_operation_is_invalid(): void
    {
        $account = Account::factory()->create();
        Capital::factory()->create(['account_id' => $account->id]);

        $this->postJson("/api/v1/accounts/{$account->id}/balance", [
            'amount' => 100.0,
            'operation' => 'multiply',
            'reason' => 'Test',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['operation']);
    }

    #[Test]
    public function update_balance_fails_validation_when_reason_missing(): void
    {
        $account = Account::factory()->create();
        Capital::factory()->create(['account_id' => $account->id]);

        $this->postJson("/api/v1/accounts/{$account->id}/balance", [
            'amount' => 100.0,
            'operation' => 'add',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }
}
