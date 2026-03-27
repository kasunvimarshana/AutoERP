<?php

declare(strict_types=1);

namespace Modules\Account\Application\UseCases;

use Modules\Account\Domain\Entities\Account;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class GetAccount
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepo) {}

    public function execute(int $id): ?Account
    {
        return $this->accountRepo->find($id);
    }
}
