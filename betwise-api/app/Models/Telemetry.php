<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telemetry extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'telemetry';

    protected $fillable = [
        'device_id',
        'round_id',
        'execution_time_ms',
        'time_drift_ms',
        'bet_placed',
        'battery_level',
        'network_type',
        'app_version',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'execution_time_ms' => 'integer',
            'time_drift_ms' => 'integer',
            'bet_placed' => 'boolean',
            'battery_level' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }
}
