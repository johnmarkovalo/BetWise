<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\MatchupSide;
use App\Enums\MatchupStatus;
use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use App\Models\AuditLog;
use App\Models\Matchup;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;

class MatchmakingService
{
    /** @var array<string, string> Provider-specific default table IDs */
    private const TABLE_DEFAULTS = [
        'evolution' => 'EVO-BACCARAT-01',
        'pragmatic' => 'PGP-BACCARAT-01',
    ];

    private const TABLE_DEFAULT_FALLBACK = 'TABLE-BACCARAT-01';

    /**
     * Manually create a matchup between two teams.
     *
     * The primary team is assigned the Banker side; the counter team the Player side.
     * Providing a null $tableId will auto-select via selectTable().
     */
    public function createMatchup(
        Team $primaryTeam,
        Team $counterTeam,
        string $provider,
        ?string $tableId = null
    ): Matchup {
        if ($primaryTeam->role !== TeamRole::Primary) {
            throw new LogicException("Team [{$primaryTeam->id}] must have role Primary.");
        }

        if ($counterTeam->role !== TeamRole::Counter) {
            throw new LogicException("Team [{$counterTeam->id}] must have role Counter.");
        }

        $tableId ??= $this->selectTable($provider);

        return DB::transaction(function () use ($primaryTeam, $counterTeam, $provider, $tableId): Matchup {
            $matchup = Matchup::query()->create([
                'provider' => $provider,
                'table_id' => $tableId,
                'status' => MatchupStatus::Active,
            ]);

            $matchup->teams()->attach($primaryTeam->id, ['side' => MatchupSide::Banker->value]);
            $matchup->teams()->attach($counterTeam->id, ['side' => MatchupSide::Player->value]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'matchup.created',
                'entity_type' => 'Matchup',
                'entity_id' => $matchup->id,
                'payload' => [
                    'provider' => $provider,
                    'table_id' => $tableId,
                    'primary_team_id' => $primaryTeam->id,
                    'counter_team_id' => $counterTeam->id,
                    'mode' => 'manual',
                ],
            ]);

            return $matchup;
        });
    }

    /**
     * Automatically find the most balanced team pair and create a matchup.
     *
     * "Most balanced" = smallest commission delta between teams
     * (i.e. the pair whose ratio of total commissions is closest to 1.0).
     */
    public function findBalancedMatchup(string $provider): Matchup
    {
        [$primaryTeam, $counterTeam] = $this->findBalancedPair($provider);

        $tableId = $this->selectTable($provider);

        return DB::transaction(function () use ($primaryTeam, $counterTeam, $provider, $tableId): Matchup {
            $matchup = Matchup::query()->create([
                'provider' => $provider,
                'table_id' => $tableId,
                'status' => MatchupStatus::Active,
            ]);

            $matchup->teams()->attach($primaryTeam->id, ['side' => MatchupSide::Banker->value]);
            $matchup->teams()->attach($counterTeam->id, ['side' => MatchupSide::Player->value]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'matchup.created',
                'entity_type' => 'Matchup',
                'entity_id' => $matchup->id,
                'payload' => [
                    'provider' => $provider,
                    'table_id' => $tableId,
                    'primary_team_id' => $primaryTeam->id,
                    'counter_team_id' => $counterTeam->id,
                    'mode' => 'auto',
                ],
            ]);

            return $matchup;
        });
    }

    /**
     * Activate a paused matchup.
     */
    public function activateMatchup(Matchup $matchup): Matchup
    {
        if ($matchup->status === MatchupStatus::Completed) {
            throw new LogicException("Cannot activate a completed matchup [{$matchup->id}].");
        }

        DB::transaction(function () use ($matchup): void {
            $matchup->update(['status' => MatchupStatus::Active]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'matchup.activated',
                'entity_type' => 'Matchup',
                'entity_id' => $matchup->id,
                'payload' => ['status' => MatchupStatus::Active->value],
            ]);
        });

        return $matchup;
    }

    /**
     * Deactivate (pause) an active matchup.
     */
    public function deactivateMatchup(Matchup $matchup): Matchup
    {
        if ($matchup->status === MatchupStatus::Completed) {
            throw new LogicException("Cannot deactivate a completed matchup [{$matchup->id}].");
        }

        DB::transaction(function () use ($matchup): void {
            $matchup->update(['status' => MatchupStatus::Paused]);

            AuditLog::query()->create([
                'actor' => 'system',
                'action' => 'matchup.deactivated',
                'entity_type' => 'Matchup',
                'entity_id' => $matchup->id,
                'payload' => ['status' => MatchupStatus::Paused->value],
            ]);
        });

        return $matchup;
    }

    /**
     * Find the most balanced Primary/Counter team pair for a given provider.
     *
     * Selects active teams that have at least one active account for the provider,
     * then picks the Primary+Counter pair with the highest balance score
     * (ratio of total commissions closest to 1.0).
     *
     * @return array{0: Team, 1: Team} [primaryTeam, counterTeam]
     */
    private function findBalancedPair(string $provider): array
    {
        $teams = Team::query()
            ->where('status', TeamStatus::Active)
            ->whereHas('accounts', fn ($q) => $q
                ->where('status', AccountStatus::Active)
                ->where('provider', $provider)
            )
            ->with(['accounts' => fn ($q) => $q
                ->where('status', AccountStatus::Active)
                ->where('provider', $provider),
            ])
            ->get();

        $primary = $teams->where('role', TeamRole::Primary)->values();
        $counter = $teams->where('role', TeamRole::Counter)->values();

        if ($primary->isEmpty() || $counter->isEmpty()) {
            throw new RuntimeException('Not enough active teams to form a matchup.');
        }

        $bestPrimary = null;
        $bestCounter = null;
        $bestScore = -1.0;

        foreach ($primary as $p) {
            foreach ($counter as $c) {
                $score = $this->calculateBalanceScore($p, $c, $provider);

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestPrimary = $p;
                    $bestCounter = $c;
                }
            }
        }

        return [$bestPrimary, $bestCounter];
    }

    /**
     * Calculate balance score for a Primary+Counter pair.
     *
     * Score = min(commissionA, commissionB) / max(commissionA, commissionB)
     * Range: 0.0 (unbalanced) → 1.0 (perfectly balanced).
     */
    private function calculateBalanceScore(Team $primary, Team $counter, string $provider): float
    {
        $sumA = $primary->accounts
            ->where('status', AccountStatus::Active)
            ->where('provider', $provider)
            ->sum(fn ($a) => (float) $a->commission_pct);

        $sumB = $counter->accounts
            ->where('status', AccountStatus::Active)
            ->where('provider', $provider)
            ->sum(fn ($a) => (float) $a->commission_pct);

        if ($sumA <= 0.0 || $sumB <= 0.0) {
            return 0.0;
        }

        return min($sumA, $sumB) / max($sumA, $sumB);
    }

    /**
     * Return the default table ID for a given provider.
     *
     * Falls back to a generic table ID for unknown providers.
     */
    private function selectTable(string $provider): string
    {
        return self::TABLE_DEFAULTS[$provider] ?? self::TABLE_DEFAULT_FALLBACK;
    }
}
