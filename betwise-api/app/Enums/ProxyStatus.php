<?php

namespace App\Enums;

enum ProxyStatus: string
{
    case Active = 'active';
    case Degraded = 'degraded';
    case Disabled = 'disabled';
}
