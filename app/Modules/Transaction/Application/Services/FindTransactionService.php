<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Transaction\Application\Contracts\FindTransactionServiceInterface;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;

class FindTransactionService extends BaseService implements FindTransactionServiceInterface
{
    public function __construct(private readonly TransactionRepositoryInterface $transactionRepository)
    {
        parent::__construct($transactionRepository);
    }

    protected function handle(array $data): mixed
    {
        return $this->transactionRepository->find($data['id'] ?? null);
    }
}
