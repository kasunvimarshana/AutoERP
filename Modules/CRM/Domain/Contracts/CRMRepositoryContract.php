<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * CRM repository contract.
 *
 * Extends the base repository contract with CRM-specific query methods.
 */
interface CRMRepositoryContract extends RepositoryContract
{
    /**
     * Find opportunities by status (tenant-scoped).
     */
    public function findByStatus(string $status): Collection;

    /**
     * Find opportunities assigned to a specific user (tenant-scoped).
     */
    public function findByAssignee(int $userId): Collection;

    /**
     * Return all customers (tenant-scoped).
     */
    public function allCustomers(): \Illuminate\Database\Eloquent\Collection;
}
