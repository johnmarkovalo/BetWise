<?php

namespace App\Models;

use App\Enums\IpType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceIp extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'device_id',
        'ip_address',
        'ip_type',
        'proxy_config',
        'active_from',
        'active_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'ip_type' => IpType::class,
            'proxy_config' => 'array',
            'active_from' => 'datetime',
            'active_until' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function ipUsageLogs(): HasMany
    {
        return $this->hasMany(IpUsageLog::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
