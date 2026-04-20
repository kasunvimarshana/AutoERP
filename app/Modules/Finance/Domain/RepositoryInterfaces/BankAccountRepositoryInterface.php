<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Finance\Domain\Entities\BankAccount;

interface BankAccountRepositoryInterface extends RepositoryInterface
{
    public function save(BankAccount $bankAccount): BankAccount;
}
