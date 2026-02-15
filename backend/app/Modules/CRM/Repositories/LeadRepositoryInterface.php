<?php

declare(strict_types=1);

namespace App\Modules\CRM\Repositories;

use App\Core\Repositories\BaseRepositoryInterface;
use App\Modules\CRM\Models\Lead;
use Illuminate\Database\Eloquent\Collection;

interface LeadRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find lead by code
     */
    public function findByCode(string $code): ?Lead;

    /**
     * Get leads by status
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get leads by source
     */
    public function getBySource(string $source): Collection;

    /**
     * Get leads assigned to user
     */
    public function getByAssignedUser(int $userId): Collection;

    /**
     * Get converted leads
     */
    public function getConvertedLeads(): Collection;

    /**
     * Get qualified leads
     */
    public function getQualifiedLeads(): Collection;

    /**
     * Get won leads
     */
    public function getWonLeads(): Collection;

    /**
     * Get lost leads
     */
    public function getLostLeads(): Collection;

    /**
     * Search leads
     */
    public function search(string $query, array $filters = []): Collection;

    /**
     * Get leads closing soon
     */
    public function getLeadsClosingSoon(int $days = 7): Collection;
}
