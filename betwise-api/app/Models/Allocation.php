<?php

namespace App\Models;

use App\Enums\AllocationOutcome;
use App\Enums\MatchupSide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Allocation extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'round_id',
        'account_id',
        'side',
        'amount',
        'outcome',
        'payout',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'side' => MatchupSide::class,
            'outcome' => AllocationOutcome::class,
            'amount' => 'decimal:2',
            'payout' => 'decimal:2',
            'executed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeSettled(Builder $query): void
    {
        $query->whereNotNull('outcome');
    }

    public function scopeForOutcome(Builder $query, AllocationOutcome $outcome): void
    {
        $query->where('outcome', $outcome);
    }
}
