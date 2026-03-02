<?php

declare(strict_types=1);

namespace Modules\CRM\Domain\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Domain\Contracts\RepositoryContract;

/**
 * CRM lead repository contract.
 *
 * Extends the base repository contract with lead-specific query methods.
 */
interface CrmLeadRepositoryContract extends RepositoryContract
{
    /**
     * Find leads by status (tenant-scoped).
     */
    public function findByStatus(string $status): Collection;

    /**
     * Find leads assigned to a specific user (tenant-scoped).
     */
    public function findByAssignee(int $userId): Collection;
}
