<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Events\AccountLowBalance;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Capital;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountService
{
    /**
     * Create an Account and its Capital record in a single transaction.
     *
     * @param  array{
     *     provider: string,
     *     commission_pct: float|string,
     *     team_id?: string|null,
     *     min_balance_threshold?: float|string,
     *     initial_balance?: float|string,
     * }  $data
     */
    public function createAccount(array $data): Account
    {
        return DB::transaction(function () use ($data): Account {
            $account = Account::query()->create([
                'provider' => $data['provider'],
                'commission_pct' => $data['commission_pct'],
                'team_id' => $data['team_id'] ?? null,
                'min_balance_threshold' => $data['min_balance_threshold'] ?? 100.00,
                'status' => AccountStatus::Active,
            ]);

            Capital::query()->create([
                'account_id' => $account->id,
                'balance' => $data['initial_balance'] ?? 0.00,
                'locked' => 0.00,
            ]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'account.created',
                'entity_type' => 'Account',
                'entity_id' => $account->id,
                'payload' => [
                    'provider' => $account->provider,
                    'commission_pct' => $account->commission_pct,
                    'team_id' => $account->team_id,
                    'initial_balance' => $data['initial_balance'] ?? 0.00,
                ],
            ]);

            return $account->load('capital');
        });
    }

    /**
     * Add or subtract an amount from an account's balance.
     *
     * After updating, triggers auto-pause check via pauseIfLowBalance().
     *
     * @param  'add'|'subtract'  $operation
     */
    public function updateBalance(Account $account, float $amount, string $operation, string $reason): Capital
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero.');
        }

        if (! in_array($operation, ['add', 'subtract'], true)) {
            throw new InvalidArgumentException("Operation must be 'add' or 'subtract'.");
        }

        $capital = DB::transaction(function () use ($account, $amount, $operation, $reason): Capital {
            $capital = Capital::query()->lockForUpdate()->find($account->id);

            $before = (float) $capital->balance;

            if ($operation === 'add') {
                $capital->balance = bcadd((string) $capital->balance, (string) $amount, 2);
            } else {
                $capital->balance = bcsub((string) $capital->balance, (string) $amount, 2);
            }

            $capital->save();

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'account.balance_updated',
                'entity_type' => 'Account',
                'entity_id' => $account->id,
                'payload' => [
                    'operation' => $operation,
                    'amount' => $amount,
                    'reason' => $reason,
                    'before' => $before,
                    'after' => (float) $capital->balance,
                ],
            ]);

            return $capital;
        });

        $this->pauseIfLowBalance($account->fresh(), $capital->fresh());

        return $capital;
    }

    /**
     * Pause the account and fire AccountLowBalance if available balance is below threshold.
     *
     * Returns true if the account was paused, false if no action was needed.
     */
    public function pauseIfLowBalance(Account $account, ?Capital $capital = null): bool
    {
        $capital ??= $account->capital;

        if ((float) $capital->available >= (float) $account->min_balance_threshold) {
            return false;
        }

        if ($account->status === AccountStatus::Paused) {
            return false;
        }

        DB::transaction(function () use ($account, $capital): void {
            $account->update(['status' => AccountStatus::Paused]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'account.auto_paused',
                'entity_type' => 'Account',
                'entity_id' => $account->id,
                'payload' => [
                    'available_balance' => (float) $capital->available,
                    'threshold' => (float) $account->min_balance_threshold,
                ],
            ]);
        });

        AccountLowBalance::dispatch($account->fresh(), $capital->fresh());

        return true;
    }
}
