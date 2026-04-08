<?php
declare(strict_types=1);
namespace Modules\Accounting\Domain\Events;
use Modules\Accounting\Domain\Entities\Account;
class AccountCreated {
    public function __construct(public readonly Account $account) {}
}
