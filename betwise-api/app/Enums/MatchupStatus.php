<?php

namespace App\Enums;

enum MatchupStatus: string
{
    case Active = 'active';
    case Paused = 'paused';
    case Completed = 'completed';
}
