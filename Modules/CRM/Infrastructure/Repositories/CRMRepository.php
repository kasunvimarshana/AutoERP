<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\CRM\Domain\Contracts\CRMRepositoryContract;
use Modules\CRM\Domain\Entities\CrmOpportunity;

/**
 * CRM repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class CRMRepository extends AbstractRepository implements CRMRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = CrmOpportunity::class;
    }

    /**
     * {@inheritdoc}
     */
    public function findByStatus(string $status): Collection
    {
        return $this->query()->where('status', $status)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByAssignee(int $userId): Collection
    {
        return $this->query()->where('assigned_to', $userId)->get();
    }

    /**
     * {@inheritdoc}
     *
     * CrmLead has HasTenant global scope; tenant isolation is enforced automatically.
     */
    public function allCustomers(): Collection
    {
        return \Modules\CRM\Domain\Entities\CrmLead::query()
            ->where('status', 'qualified')
            ->orWhere('status', 'converted')
            ->get();
    }
}
