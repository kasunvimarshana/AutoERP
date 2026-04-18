<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteAccountServiceInterface;
use Modules\Finance\Domain\Exceptions\AccountNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\AccountRepositoryInterface;

class DeleteAccountService extends BaseService implements DeleteAccountServiceInterface
{
    public function __construct(private readonly AccountRepositoryInterface $accountRepository)
    {
        parent::__construct($accountRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        $account = $this->accountRepository->find($id);

        if (! $account) {
            throw new AccountNotFoundException($id);
        }

        return $this->accountRepository->delete($id);
    }
}
