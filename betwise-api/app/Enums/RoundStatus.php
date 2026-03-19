<?php

namespace App\Enums;

enum RoundStatus: string
{
    case Preparing = 'preparing';
    case Prepared = 'prepared';
    case Executing = 'executing';
    case Completed = 'completed';
    case Aborted = 'aborted';
}
