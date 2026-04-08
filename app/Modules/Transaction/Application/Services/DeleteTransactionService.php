<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Transaction\Application\Contracts\DeleteTransactionServiceInterface;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\Exceptions\TransactionNotFoundException;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;

class DeleteTransactionService extends BaseService implements DeleteTransactionServiceInterface
{
    public function __construct(private readonly TransactionRepositoryInterface $transactionRepository)
    {
        parent::__construct($transactionRepository);
    }

    protected function handle(array $data): bool
    {
        $id = $data['id'];

        /** @var Transaction|null $transaction */
        $transaction = $this->transactionRepository->find($id);
        if (! $transaction) {
            throw new TransactionNotFoundException($id);
        }

        $this->transactionRepository->delete($id);

        return true;
    }
}
