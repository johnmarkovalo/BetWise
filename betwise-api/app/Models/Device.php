<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Device extends Model
{
    use HasFactory, HasUuids;

    protected $hidden = ['auth_token'];

    protected $fillable = [
        'name',
        'android_id',
        'auth_token',
        'account_id',
        'status',
        'last_seen',
        'battery_level',
        'app_version',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeviceStatus::class,
            'last_seen' => 'datetime',
            'battery_level' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function deviceIps(): HasMany
    {
        return $this->hasMany(DeviceIp::class);
    }

    public function activeIp(): HasOne
    {
        return $this->hasOne(DeviceIp::class)->where('is_active', true);
    }

    public function telemetry(): HasMany
    {
        return $this->hasMany(Telemetry::class);
    }

    public function scopeOnline(Builder $query): void
    {
        $query->where('status', DeviceStatus::Online);
    }
}
