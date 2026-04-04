<?php
namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\UpdateAccountServiceInterface;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Repositories\AccountRepositoryInterface;

class UpdateAccountService implements UpdateAccountServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
    ) {}

    public function execute(Account $account, array $data): Account
    {
        return $this->accountRepository->update($account, $data);
    }
}
