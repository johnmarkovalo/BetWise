<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpUsageLog extends Model
{
    use HasFactory, HasUuids;

    public const CREATED_AT = 'used_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'device_ip_id',
        'provider',
        'account_id',
        'round_id',
        'used_at',
        'success',
        'flagged',
    ];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'success' => 'boolean',
            'flagged' => 'boolean',
        ];
    }

    public function deviceIp(): BelongsTo
    {
        return $this->belongsTo(DeviceIp::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    public function scopeFlagged(Builder $query): void
    {
        $query->where('flagged', true);
    }
}
