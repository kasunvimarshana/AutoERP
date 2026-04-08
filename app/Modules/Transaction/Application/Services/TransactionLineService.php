<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Core\Domain\Exceptions\NotFoundException;
use Modules\Transaction\Application\Contracts\TransactionLineServiceInterface;
use Modules\Transaction\Domain\Entities\TransactionLine;
use Modules\Transaction\Domain\RepositoryInterfaces\TransactionLineRepositoryInterface;

class TransactionLineService implements TransactionLineServiceInterface
{
    public function __construct(
        private readonly TransactionLineRepositoryInterface $transactionLineRepository,
    ) {}

    public function getLine(string $tenantId, string $id): TransactionLine
    {
        $line = $this->transactionLineRepository->findById($tenantId, $id);

        if ($line === null) {
            throw new NotFoundException('TransactionLine', $id);
        }

        return $line;
    }

    public function getLinesForTransaction(string $tenantId, string $transactionId): array
    {
        return $this->transactionLineRepository->findByTransaction($tenantId, $transactionId);
    }

    public function addLine(string $tenantId, array $data): TransactionLine
    {
        return DB::transaction(function () use ($tenantId, $data): TransactionLine {
            $now = now();

            $line = new TransactionLine(
                id: (string) Str::uuid(),
                tenantId: $tenantId,
                transactionId: (string) ($data['transaction_id'] ?? ''),
                accountId: isset($data['account_id']) ? (string) $data['account_id'] : null,
                productId: isset($data['product_id']) ? (string) $data['product_id'] : null,
                quantity: (float) ($data['quantity'] ?? 1.0),
                unitPrice: (float) ($data['unit_price'] ?? 0.0),
                amount: (float) ($data['amount'] ?? 0.0),
                debit: (float) ($data['debit'] ?? 0.0),
                credit: (float) ($data['credit'] ?? 0.0),
                notes: isset($data['notes']) ? (string) $data['notes'] : null,
                createdAt: $now,
                updatedAt: $now,
            );

            $this->transactionLineRepository->save($line);

            return $line;
        });
    }

    public function updateLine(string $tenantId, string $id, array $data): TransactionLine
    {
        return DB::transaction(function () use ($tenantId, $id, $data): TransactionLine {
            $existing = $this->getLine($tenantId, $id);
            $now = now();

            $updated = new TransactionLine(
                id: $existing->id,
                tenantId: $existing->tenantId,
                transactionId: $existing->transactionId,
                accountId: isset($data['account_id']) ? (string) $data['account_id'] : $existing->accountId,
                productId: isset($data['product_id']) ? (string) $data['product_id'] : $existing->productId,
                quantity: isset($data['quantity']) ? (float) $data['quantity'] : $existing->quantity,
                unitPrice: isset($data['unit_price']) ? (float) $data['unit_price'] : $existing->unitPrice,
                amount: isset($data['amount']) ? (float) $data['amount'] : $existing->amount,
                debit: isset($data['debit']) ? (float) $data['debit'] : $existing->debit,
                credit: isset($data['credit']) ? (float) $data['credit'] : $existing->credit,
                notes: isset($data['notes']) ? (string) $data['notes'] : $existing->notes,
                createdAt: $existing->createdAt,
                updatedAt: $now,
            );

            $this->transactionLineRepository->save($updated);

            return $updated;
        });
    }

    public function deleteLine(string $tenantId, string $id): void
    {
        $this->transactionLineRepository->delete($tenantId, $id);
    }
}
