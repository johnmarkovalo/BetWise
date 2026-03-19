<?php

namespace App\Models;

use App\Enums\RoundStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Round extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['matchup_id', 'execute_at', 'status', 'seed', 'total_capital'];

    protected function casts(): array
    {
        return [
            'status' => RoundStatus::class,
            'execute_at' => 'integer',
            'total_capital' => 'decimal:2',
        ];
    }

    public function matchup(): BelongsTo
    {
        return $this->belongsTo(Matchup::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(Telemetry::class);
    }

    public function ipUsageLogs(): HasMany
    {
        return $this->hasMany(IpUsageLog::class);
    }

    public function scopePrepared(Builder $query): void
    {
        $query->where('status', RoundStatus::Prepared);
    }

    public function scopeExecuting(Builder $query): void
    {
        $query->where('status', RoundStatus::Executing);
    }

    public function scopeCompleted(Builder $query): void
    {
        $query->where('status', RoundStatus::Completed);
    }

    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', [RoundStatus::Preparing, RoundStatus::Prepared]);
    }
}
