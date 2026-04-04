<?php
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Domain\Entities\Account;

interface UpdateAccountServiceInterface
{
    public function execute(Account $account, array $data): Account;
}
