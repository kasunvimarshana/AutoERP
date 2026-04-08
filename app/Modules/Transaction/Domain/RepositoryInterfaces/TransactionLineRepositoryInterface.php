<?php

declare(strict_types=1);

namespace Modules\Transaction\Domain\RepositoryInterfaces;

use Modules\Transaction\Domain\Entities\TransactionLine;

interface TransactionLineRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?TransactionLine;

    /** @return TransactionLine[] */
    public function findByTransaction(string $tenantId, string $transactionId): array;

    public function save(TransactionLine $line): void;

    public function delete(string $tenantId, string $id): void;
}
