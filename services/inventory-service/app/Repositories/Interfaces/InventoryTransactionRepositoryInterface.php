<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\InventoryTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryTransactionRepositoryInterface
{
    /**
     * Retrieve a paginated list of transactions with optional filters.
     *
     * @param  array<string, mixed> $filters
     */
    public function getAll(array $filters = []): LengthAwarePaginator;

    /**
     * Find a transaction by its primary key.
     */
    public function findById(int $id): ?InventoryTransaction;

    /**
     * Create a new transaction record.
     *
     * @param  array<string, mixed> $data
     */
    public function create(array $data): InventoryTransaction;
}
