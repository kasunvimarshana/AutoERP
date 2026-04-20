<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteBankAccountServiceInterface;
use Modules\Finance\Domain\Exceptions\BankAccountNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankAccountRepositoryInterface;

class DeleteBankAccountService extends BaseService implements DeleteBankAccountServiceInterface
{
    public function __construct(private readonly BankAccountRepositoryInterface $bankAccountRepository)
    {
        parent::__construct($bankAccountRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->bankAccountRepository->find($id)) {
            throw new BankAccountNotFoundException($id);
        }

        return $this->bankAccountRepository->delete($id);
    }
}
