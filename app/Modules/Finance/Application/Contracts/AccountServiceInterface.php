<?php

declare(strict_types=1);

namespace Modules\Finance\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Finance\Application\DTOs\AccountData;

interface AccountServiceInterface
{
    /**
     * Create a new Chart of Accounts entry.
     */
    public function create(AccountData $dto, int $tenantId): mixed;

    /**
     * Update an existing account.
     */
    public function update(int $id, AccountData $dto): mixed;

    /**
     * Delete an account (soft delete; system accounts are protected).
     */
    public function delete(int $id): bool;

    /**
     * Find an account by primary key.
     */
    public function find(mixed $id): mixed;

    /**
     * Find an account by its code within a tenant.
     */
    public function findByCode(string $code, int $tenantId): mixed;

    /**
     * Retrieve all accounts of a given type for a tenant.
     */
    public function findByType(string $type, int $tenantId): Collection;

    /**
     * Retrieve the hierarchical Chart of Accounts tree for a tenant.
     */
    public function getChartOfAccounts(int $tenantId): Collection;

    /**
     * Paginated list of accounts with optional filters.
     */
    public function list(array $filters = [], ?int $perPage = null): mixed;
}
