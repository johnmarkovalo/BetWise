<?php

namespace App\Models;

use App\Enums\MatchupSide;
use Illuminate\Database\Eloquent\Relations\Pivot;

class MatchupTeam extends Pivot
{
    public $incrementing = false;

    protected $table = 'matchup_teams';

    protected function casts(): array
    {
        return [
            'side' => MatchupSide::class,
        ];
    }
}
