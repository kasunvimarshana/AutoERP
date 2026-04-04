<?php
namespace Modules\Accounting\Application\Services;

use Modules\Accounting\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Accounting\Domain\Entities\Account;
use Modules\Accounting\Domain\Repositories\AccountRepositoryInterface;

class DeleteAccountService implements DeleteAccountServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
    ) {}

    public function execute(Account $account): bool
    {
        return $this->accountRepository->delete($account);
    }
}
