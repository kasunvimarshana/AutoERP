<?php

declare(strict_types=1);

namespace Modules\Sales\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Repositories\Contracts\BaseRepositoryInterface;
use Modules\Sales\Entities\Lead;

/**
 * Lead Repository Interface
 *
 * Defines the contract for lead data access operations.
 */
interface LeadRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find lead by lead number.
     */
    public function findByLeadNumber(string $leadNumber): ?Lead;

    /**
     * Get leads by status.
     */
    public function getLeadsByStatus(string $status): Collection;

    /**
     * Get leads by stage.
     */
    public function getLeadsByStage(string $stage): Collection;

    /**
     * Get leads assigned to a user.
     */
    public function getLeadsAssignedTo(int $userId): Collection;

    /**
     * Search leads by contact name or email.
     */
    public function search(string $query): Collection;
}
