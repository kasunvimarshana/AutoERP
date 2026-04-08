<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Modules\Finance\Application\DTOs\TransactionData;

interface TransactionServiceInterface
{
    /**
     * Create a new financial transaction.
     */
    public function create(TransactionData $dto, int $tenantId): mixed;

    /**
     * Find a transaction by primary key.
     */
    public function find(mixed $id): mixed;

    /**
     * Paginated list of transactions with optional filters.
     */
    public function list(array $filters = [], ?int $perPage = null): mixed;
}
