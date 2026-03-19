<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor',
        'action',
        'entity_type',
        'entity_id',
        'payload',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function scopeForActor(Builder $query, string $actor): void
    {
        $query->where('actor', $actor);
    }

    public function scopeForAction(Builder $query, string $action): void
    {
        $query->where('action', $action);
    }

    public function scopeForEntity(Builder $query, string $type, string $id): void
    {
        $query->where('entity_type', $type)->where('entity_id', $id);
    }
}
