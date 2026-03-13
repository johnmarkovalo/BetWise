<?php

namespace App\Enums;

enum ProxyProtocol: string
{
    case Http = 'http';
    case Https = 'https';
    case Socks4 = 'socks4';
    case Socks5 = 'socks5';
}
