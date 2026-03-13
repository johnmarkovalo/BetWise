<?php

namespace App\Enums;

enum AllocationOutcome: string
{
    case Win = 'win';
    case Loss = 'loss';
    case Tie = 'tie';
    case Push = 'push';
}
