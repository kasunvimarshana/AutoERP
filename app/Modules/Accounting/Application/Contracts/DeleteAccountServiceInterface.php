<?php
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Domain\Entities\Account;

interface DeleteAccountServiceInterface
{
    public function execute(Account $account): bool;
}
