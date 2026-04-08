<?php

declare(strict_types=1);

namespace Modules\Transaction\Application\Contracts;

use DateTimeInterface;
use Modules\Transaction\Domain\Entities\Transaction;

interface TransactionServiceInterface
{
    public function getTransaction(string $tenantId, string $id): Transaction;

    /** @return Transaction[] */
    public function getAllTransactions(string $tenantId): array;

    public function createTransaction(string $tenantId, array $data): Transaction;

    public function postTransaction(string $tenantId, string $id): Transaction;

    public function voidTransaction(string $tenantId, string $id): Transaction;

    /** @return Transaction[] */
    public function getTransactionsByDateRange(string $tenantId, DateTimeInterface $from, DateTimeInterface $to): array;
}
