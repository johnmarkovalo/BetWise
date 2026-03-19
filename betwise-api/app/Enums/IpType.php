<?php

namespace App\Enums;

enum IpType: string
{
    case Direct = 'direct';
    case Proxy = 'proxy';
    case Vpn = 'vpn';
}
