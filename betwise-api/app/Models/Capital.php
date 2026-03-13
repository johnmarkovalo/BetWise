<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Capital extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    protected $primaryKey = 'account_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['account_id', 'balance', 'locked'];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'locked' => 'decimal:2',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function getAvailableAttribute(): string
    {
        return bcsub((string) $this->balance, (string) $this->locked, 2);
    }
}
