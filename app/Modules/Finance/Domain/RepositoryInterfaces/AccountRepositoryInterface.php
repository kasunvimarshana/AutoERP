<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface AccountRepositoryInterface extends RepositoryInterface
{
    /**
     * Find an account by its code within a tenant.
     */
    public function findByCode(string $code, int $tenantId): mixed;

    /**
     * Retrieve all accounts of a given type for a tenant.
     */
    public function findByType(string $type, int $tenantId): Collection;

    /**
     * Retrieve the full Chart of Accounts for a tenant, structured hierarchically.
     */
    public function getChartOfAccounts(int $tenantId): Collection;

    /**
     * Update the current_balance of an account by applying a debit or credit amount.
     *
     * @param  string  $side  'debit' or 'credit'
     */
    public function updateBalance(int $accountId, float $amount, string $side): void;

    /**
     * Find an account by UUID.
     */
    public function findByUuid(string $uuid): mixed;

    /**
     * Find all root-level accounts (parent_id IS NULL) for a tenant.
     */
    public function findRootAccounts(int $tenantId): Collection;
}
