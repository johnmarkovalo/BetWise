<?php

namespace App\Enums;

enum MatchupSide: string
{
    case Banker = 'banker';
    case Player = 'player';
    case Tie = 'tie';
}
