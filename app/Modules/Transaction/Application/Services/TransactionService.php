<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Transaction\Application\Contracts\TransactionServiceInterface;
use Modules\Transaction\Domain\Entities\Transaction;
use Modules\Transaction\Domain\Entities\TransactionLine;
use Modules\Transaction\Domain\Events\TransactionCreated;
use Modules\Transaction\Domain\Events\TransactionPosted;
use Modules\Transaction\Domain\Events\TransactionVoided;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionLineRepositoryInterface;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionRepositoryInterface;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly TransactionLineRepositoryInterface $transactionLineRepository,
    ) {}

    public function getTransaction(string $tenantId, string $id): Transaction
    {
        $transaction = $this->transactionRepository->findById($tenantId, $id);

        if ($transaction === null) {
            throw new NotFoundException('Transaction', $id);
        }

        return $transaction;
    }

    public function getAllTransactions(string $tenantId): array
    {
        return $this->transactionRepository->findAll($tenantId);
    }

    public function createTransaction(string $tenantId, array $data): Transaction
    {
        return DB::transaction(function () use ($tenantId, $data): Transaction {
            $now = now();
            $lines = $data['lines'] ?? [];

            if (($data['type'] ?? '') === 'journal' && count($lines) > 0) {
                $totalDebit  = array_sum(array_column($lines, 'debit'));
                $totalCredit = array_sum(array_column($lines, 'credit'));

                if (abs($totalDebit - $totalCredit) >= PHP_FLOAT_EPSILON) {
                    throw new \InvalidArgumentException(
                        'Journal transaction debits must equal credits.'
                    );
                }
            }

            $transaction = new Transaction(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                type: (string) ($data['type'] ?? ''),
                referenceType: isset($data['reference_type']) ? (string) $data['reference_type'] : null,
                referenceId: isset($data['reference_id']) ? (string) $data['reference_id'] : null,
                status: 'pending',
                description: isset($data['description']) ? (string) $data['description'] : null,
                transactionDate: isset($data['transaction_date'])
                    ? new \DateTimeImmutable((string) $data['transaction_date'])
                    : new \DateTimeImmutable(),
                totalAmount: (float) ($data['total_amount'] ?? 0.0),
                createdAt: $now,
                updatedAt: $now,
            );

            $this->transactionRepository->save($transaction);

            foreach ($lines as $lineData) {
                $line = new TransactionLine(
                    id: (string) Str::uuid(),
                    tenantId: $tenantId,
                    transactionId: $transaction->id,
                    accountId: isset($lineData['account_id']) ? (string) $lineData['account_id'] : null,
                    productId: isset($lineData['product_id']) ? (string) $lineData['product_id'] : null,
                    quantity: (float) ($lineData['quantity'] ?? 1.0),
                    unitPrice: (float) ($lineData['unit_price'] ?? 0.0),
                    amount: (float) ($lineData['amount'] ?? 0.0),
                    debit: (float) ($lineData['debit'] ?? 0.0),
                    credit: (float) ($lineData['credit'] ?? 0.0),
                    notes: isset($lineData['notes']) ? (string) $lineData['notes'] : null,
                    createdAt: $now,
                    updatedAt: $now,
                );

                $this->transactionLineRepository->save($line);
            }

            Event::dispatch(new TransactionCreated($transaction));

            return $transaction;
        });
    }

    public function postTransaction(string $tenantId, string $id): Transaction
    {
        return DB::transaction(function () use ($tenantId, $id): Transaction {
            $transaction = $this->getTransaction($tenantId, $id);
            $posted = $transaction->post();
            $this->transactionRepository->save($posted);
            Event::dispatch(new TransactionPosted($posted));

            return $posted;
        });
    }

    public function voidTransaction(string $tenantId, string $id): Transaction
    {
        return DB::transaction(function () use ($tenantId, $id): Transaction {
            $transaction = $this->getTransaction($tenantId, $id);

            if ($transaction->isVoided()) {
                throw new \InvalidArgumentException('Transaction is already voided.');
            }

            $voided = $transaction->void();
            $this->transactionRepository->save($voided);
            Event::dispatch(new TransactionVoided($voided));

            return $voided;
        });
    }

    public function getTransactionsByDateRange(string $tenantId, DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->transactionRepository->findByDateRange($tenantId, $from, $to);
    }
}
