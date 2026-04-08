<?php

declare(strict_types=1);

namespace Modules\Financial\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface BankTransactionRepositoryInterface extends RepositoryInterface
{
    /**
     * Return all transactions for a given bank account, sorted by date descending.
     */
    public function findByBankAccount(string $bankAccountId): Collection;

    /**
     * Return transactions by status for a given bank account.
     */
    public function findByStatus(string $bankAccountId, string $status): Collection;

    /**
     * Return unreconciled (pending/matched) transactions for a bank account within a date range.
     */
    public function findUnreconciled(
        string $bankAccountId,
        ?string $fromDate = null,
        ?string $toDate = null,
    ): Collection;
}
