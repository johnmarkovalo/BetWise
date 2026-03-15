<?php

namespace App\Events;

use App\Models\Account;
use App\Models\Capital;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountLowBalance
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Account $account,
        public readonly Capital $capital,
    ) {}
}
