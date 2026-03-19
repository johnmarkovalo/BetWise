<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\TeamRole;
use App\Enums\TeamStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['name', 'role', 'status'];

    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
            'status' => TeamStatus::class,
        ];
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function matchups(): BelongsToMany
    {
        return $this->belongsToMany(Matchup::class, 'matchup_teams')
            ->withPivot('side')
            ->using(MatchupTeam::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', TeamStatus::Active);
    }

    public function scopePrimary(Builder $query): void
    {
        $query->where('role', TeamRole::Primary);
    }

    public function scopeCounter(Builder $query): void
    {
        $query->where('role', TeamRole::Counter);
    }

    public function scopeWithActiveAccounts(Builder $query): void
    {
        $query->whereHas('accounts', fn (Builder $q) => $q->where('status', AccountStatus::Active));
    }
}
