<?php

namespace App\Models;

use App\Enums\MatchupStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matchup extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['provider', 'table_id', 'status', 'locked_at'];

    protected function casts(): array
    {
        return [
            'status' => MatchupStatus::class,
            'locked_at' => 'datetime',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'matchup_teams')
            ->withPivot('side')
            ->using(MatchupTeam::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(Round::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', MatchupStatus::Active);
    }
}
