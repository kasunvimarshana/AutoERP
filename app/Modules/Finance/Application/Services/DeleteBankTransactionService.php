<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Finance\Application\Contracts\DeleteBankTransactionServiceInterface;
use Modules\Finance\Domain\Exceptions\BankTransactionNotFoundException;
use Modules\Finance\Domain\RepositoryInterfaces\BankTransactionRepositoryInterface;

class DeleteBankTransactionService extends BaseService implements DeleteBankTransactionServiceInterface
{
    public function __construct(private readonly BankTransactionRepositoryInterface $bankTransactionRepository)
    {
        parent::__construct($bankTransactionRepository);
    }

    protected function handle(array $data): bool
    {
        $id = (int) ($data['id'] ?? 0);
        if (! $this->bankTransactionRepository->find($id)) {
            throw new BankTransactionNotFoundException($id);
        }

        return $this->bankTransactionRepository->delete($id);
    }
}
