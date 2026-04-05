<?php
declare(strict_types=1);
namespace Modules\POS\Application\Services;

use Modules\POS\Domain\Entities\PosTransaction;
use Modules\POS\Domain\Exceptions\PosTransactionNotFoundException;
use Modules\POS\Domain\RepositoryInterfaces\PosTransactionRepositoryInterface;

class VoidPosTransactionService
{
    public function __construct(
        private readonly PosTransactionRepositoryInterface $transactionRepository,
    ) {}

    public function void(int $transactionId): PosTransaction
    {
        $transaction = $this->transactionRepository->findById($transactionId);
        if ($transaction === null) {
            throw new PosTransactionNotFoundException($transactionId);
        }

        $transaction->void();

        return $this->transactionRepository->updateStatus(
            $transactionId,
            PosTransaction::STATUS_VOIDED
        ) ?? $transaction;
    }
}
