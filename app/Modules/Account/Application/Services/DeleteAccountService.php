<?php

declare(strict_types=1);

namespace Modules\Account\Application\Services;

use Modules\Account\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Account\Domain\Events\AccountDeleted;
use Modules\Account\Domain\Exceptions\AccountNotFoundException;
use Modules\Account\Domain\RepositoryInterfaces\AccountRepositoryInterface;
use Modules\Core\Application\Services\BaseService;

class DeleteAccountService extends BaseService implements DeleteAccountServiceInterface
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepository)
    {
        parent::__construct($accountRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];
        $account = $this->accountRepository->find($id);

        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        $tenantId = $account->getTenantId();
        $deleted = $this->accountRepository->delete($id);

        if ($deleted) {
            $this->addEvent(new AccountDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
