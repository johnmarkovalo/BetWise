<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\TeamStatus;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TeamService
{
    /**
     * Create a new team and write an audit log entry.
     *
     * @param  array{name: string, role: string, status?: string}  $data
     */
    public function createTeam(array $data): Team
    {
        return DB::transaction(function () use ($data): Team {
            $team = Team::query()->create($data);
            $team->refresh();

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'team.created',
                'entity_type' => 'Team',
                'entity_id' => $team->id,
                'payload' => [
                    'name' => $team->name,
                    'role' => $team->role->value,
                    'status' => $team->status->value,
                ],
            ]);

            return $team;
        });
    }

    /**
     * Update an existing team and write an audit log entry.
     *
     * @param  array{name?: string, role?: string, status?: string}  $data
     */
    public function updateTeam(Team $team, array $data): Team
    {
        DB::transaction(function () use ($team, $data): void {
            $before = $team->only(['name', 'role', 'status']);

            $team->update($data);
            $team->refresh();

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'team.updated',
                'entity_type' => 'Team',
                'entity_id' => $team->id,
                'payload' => [
                    'before' => $before,
                    'after' => $team->only(['name', 'role', 'status']),
                ],
            ]);
        });

        return $team;
    }

    /**
     * Assign one or more accounts to a team by ID.
     *
     * @param  string[]  $accountIds
     */
    public function assignAccounts(Team $team, array $accountIds): void
    {
        DB::transaction(function () use ($team, $accountIds): void {
            Account::query()
                ->whereIn('id', $accountIds)
                ->update(['team_id' => $team->id]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'team.accounts_assigned',
                'entity_type' => 'Team',
                'entity_id' => $team->id,
                'payload' => ['account_ids' => $accountIds],
            ]);
        });
    }

    /**
     * Remove a single account from its team (sets team_id to null).
     */
    public function removeAccount(Team $team, Account $account): void
    {
        if ($account->team_id !== $team->id) {
            throw new InvalidArgumentException("Account [{$account->id}] does not belong to team [{$team->id}].");
        }

        DB::transaction(function () use ($team, $account): void {
            $account->update(['team_id' => null]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'team.account_removed',
                'entity_type' => 'Team',
                'entity_id' => $team->id,
                'payload' => ['account_id' => $account->id],
            ]);
        });
    }

    /**
     * Validate whether a team is composition-ready for betting rounds.
     *
     * Returns an array of validation errors; empty means the team is valid.
     *
     * @return string[]
     */
    public function validateTeamComposition(Team $team): array
    {
        $errors = [];

        $team->loadMissing('accounts');

        if ($team->status !== TeamStatus::Active) {
            $errors[] = 'Team is not active.';
        }

        $activeAccounts = $team->accounts->filter(
            fn (Account $account) => $account->status === AccountStatus::Active
        );

        if ($activeAccounts->isEmpty()) {
            $errors[] = 'Team has no active accounts.';
        }

        $hasDuplicateProvider = $activeAccounts
            ->pluck('provider')
            ->duplicates()
            ->isNotEmpty();

        if ($hasDuplicateProvider) {
            $errors[] = 'Team has multiple active accounts on the same provider.';
        }

        return $errors;
    }

    /**
     * Return aggregate statistics for a team.
     *
     * @return array{
     *     account_count: int,
     *     active_account_count: int,
     *     total_commission_pct: float,
     *     average_commission_pct: float,
     *     min_commission_pct: float,
     *     max_commission_pct: float,
     * }
     */
    public function getTeamStats(Team $team): array
    {
        $accounts = $team->accounts()->get();
        $activeAccounts = $accounts->where('status', AccountStatus::Active);

        $commissions = $activeAccounts->pluck('commission_pct')->map(fn ($v) => (float) $v);

        return [
            'account_count' => $accounts->count(),
            'active_account_count' => $activeAccounts->count(),
            'total_commission_pct' => round($commissions->sum(), 4),
            'average_commission_pct' => $commissions->isNotEmpty()
                ? round($commissions->average(), 4)
                : 0.0,
            'min_commission_pct' => $commissions->isNotEmpty()
                ? round((float) $commissions->min(), 4)
                : 0.0,
            'max_commission_pct' => $commissions->isNotEmpty()
                ? round((float) $commissions->max(), 4)
                : 0.0,
        ];
    }
}
