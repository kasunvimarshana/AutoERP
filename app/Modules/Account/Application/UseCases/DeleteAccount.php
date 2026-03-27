<?php

declare(strict_types=1);

namespace Modules\Account\Application\UseCases;

use Modules\Account\Domain\Events\AccountDeleted;
use Modules\Account\Domain\Exceptions\AccountNotFoundException;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class DeleteAccount
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepo) {}

    public function execute(int $id): bool
    {
        $account = $this->accountRepo->find($id);
        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        $tenantId = $account->getTenantId();
        $deleted = $this->accountRepo->delete($id);

        if ($deleted) {
            event(new AccountDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
