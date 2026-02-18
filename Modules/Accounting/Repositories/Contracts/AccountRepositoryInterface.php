<?php

declare(strict_types=1);

namespace Modules\Accounting\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Accounting\Entities\Account;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;

/**
 * Account Repository Interface
 *
 * Defines the contract for account data access operations.
 */
interface AccountRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find account by account number.
     */
    public function findByAccountNumber(string $accountNumber): ?Account;

    /**
     * Get accounts by type.
     */
    public function getByType(string $type): Collection;

    /**
     * Get active accounts.
     */
    public function getActiveAccounts(): Collection;

    /**
     * Get chart of accounts in hierarchical structure.
     */
    public function getChartOfAccounts(): Collection;

    /**
     * Get child accounts of a parent.
     */
    public function getChildAccounts(int $parentId): Collection;

    /**
     * Search accounts by name or number.
     */
    public function search(string $query): Collection;
}
