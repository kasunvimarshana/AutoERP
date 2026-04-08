<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\RepositoryInterfaces;

use DateTimeInterface;
use Modules\Transaction\Domain\Entities\Transaction;

interface TransactionRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Transaction;

    /** @return Transaction[] */
    public function findAll(string $tenantId): array;

    /** @return Transaction[] */
    public function findByType(string $tenantId, string $type): array;

    /** @return Transaction[] */
    public function findByStatus(string $tenantId, string $status): array;

    /** @return Transaction[] */
    public function findByReference(string $tenantId, string $referenceType, string $referenceId): array;

    /** @return Transaction[] */
    public function findByDateRange(string $tenantId, DateTimeInterface $from, DateTimeInterface $to): array;

    public function save(Transaction $transaction): void;

    public function delete(string $tenantId, string $id): void;
}
