<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Transaction\Application\Contracts\PostTransactionServiceInterface;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\Events\TransactionPosted;
use Modules\Transaction\Domain\Exceptions\TransactionNotFoundException;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;

class PostTransactionService extends BaseService implements PostTransactionServiceInterface
{
    public function __construct(private readonly TransactionRepositoryInterface $transactionRepository)
    {
        parent::__construct($transactionRepository);
    }

    protected function handle(array $data): Transaction
    {
        $id = $data['id'];

        /** @var Transaction|null $transaction */
        $transaction = $this->transactionRepository->find($id);
        if (! $transaction) {
            throw new TransactionNotFoundException($id);
        }

        $transaction->post();
        $saved = $this->transactionRepository->save($transaction);
        $this->addEvent(new TransactionPosted($saved));

        return $saved;
    }
}
