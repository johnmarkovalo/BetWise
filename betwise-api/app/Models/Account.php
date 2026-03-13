<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Account extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'provider',
        'commission_pct',
        'team_id',
        'status',
        'min_balance_threshold',
    ];

    protected function casts(): array
    {
        return [
            'commission_pct' => 'decimal:2',
            'min_balance_threshold' => 'decimal:2',
            'status' => AccountStatus::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function capital(): HasOne
    {
        return $this->hasOne(Capital::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(Allocation::class);
    }

    public function ipUsageLogs(): HasMany
    {
        return $this->hasMany(IpUsageLog::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', AccountStatus::Active);
    }

    public function scopeLowBalance(Builder $query): void
    {
        $query->join('capitals', 'capitals.account_id', '=', 'accounts.id')
            ->whereRaw('(capitals.balance - capitals.locked) < accounts.min_balance_threshold');
    }
}
