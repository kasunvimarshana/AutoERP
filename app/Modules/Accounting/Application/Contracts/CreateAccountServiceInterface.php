<?php
namespace Modules\Accounting\Application\Contracts;

use Modules\Accounting\Application\DTOs\AccountData;
use Modules\Accounting\Domain\Entities\Account;

interface CreateAccountServiceInterface
{
    public function execute(AccountData $data): Account;
}
