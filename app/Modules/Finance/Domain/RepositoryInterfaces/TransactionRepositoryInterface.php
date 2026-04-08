<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface TransactionRepositoryInterface extends RepositoryInterface
{
    /**
     * Find a transaction by its reference number within a tenant.
     */
    public function findByReference(string $referenceNumber, int $tenantId): mixed;

    /**
     * Retrieve transactions for an account within a date range.
     */
    public function findByAccount(int $accountId, int $tenantId, ?string $fromDate = null, ?string $toDate = null): Collection;

    /**
     * Retrieve transactions by type for a tenant.
     */
    public function findByType(string $type, int $tenantId): Collection;

    /**
     * Generate the next reference number for a transaction.
     */
    public function nextReferenceNumber(int $tenantId): string;
}
