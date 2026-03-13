<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IpConflictRule extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'provider',
        'max_concurrent_devices',
        'cooldown_seconds',
        'hourly_limit',
        'require_unique_per_team',
    ];

    protected function casts(): array
    {
        return [
            'max_concurrent_devices' => 'integer',
            'cooldown_seconds' => 'integer',
            'hourly_limit' => 'integer',
            'require_unique_per_team' => 'boolean',
        ];
    }
}
