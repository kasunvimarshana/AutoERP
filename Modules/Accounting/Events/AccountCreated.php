<?php

declare(strict_types=1);

namespace Modules\Accounting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\Models\Account;

class AccountCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Account $account
    ) {}
}
