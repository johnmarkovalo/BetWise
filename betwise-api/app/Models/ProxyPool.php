<?php

namespace App\Models;

use App\Enums\ProxyProtocol;
use App\Enums\ProxyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProxyPool extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'proxy_pool';

    protected $hidden = ['password_encrypted'];

    protected $fillable = [
        'ip_address',
        'port',
        'protocol',
        'username',
        'password_encrypted',
        'geographic_region',
        'status',
        'health_score',
        'total_uses',
        'failed_uses',
        'banned_by_providers',
        'last_health_check',
    ];

    protected function casts(): array
    {
        return [
            'protocol' => ProxyProtocol::class,
            'status' => ProxyStatus::class,
            'health_score' => 'decimal:2',
            'total_uses' => 'integer',
            'failed_uses' => 'integer',
            'banned_by_providers' => 'array',
            'last_health_check' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', ProxyStatus::Active);
    }

    public function scopeHealthy(Builder $query): void
    {
        $query->where('health_score', '>', 0.7);
    }
}
