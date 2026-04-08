<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Transaction\Application\Contracts\VoidTransactionServiceInterface;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\Events\TransactionVoided;
use Modules\Transaction\Domain\Exceptions\TransactionNotFoundException;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;

class VoidTransactionService extends BaseService implements VoidTransactionServiceInterface
{
    public function __construct(private readonly TransactionRepositoryInterface $transactionRepository)
    {
        parent::__construct($transactionRepository);
    }

    protected function handle(array $data): Transaction
    {
        $id     = $data['id'];
        $reason = $data['reason'] ?? '';

        /** @var Transaction|null $transaction */
        $transaction = $this->transactionRepository->find($id);
        if (! $transaction) {
            throw new TransactionNotFoundException($id);
        }

        $transaction->void($reason);
        $saved = $this->transactionRepository->save($transaction);
        $this->addEvent(new TransactionVoided($saved));

        return $saved;
    }
}
