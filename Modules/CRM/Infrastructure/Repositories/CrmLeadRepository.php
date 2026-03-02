<?php

declare(strict_types=1);

namespace Modules\CRM\Infrastructure\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Infrastructure\Repositories\AbstractRepository;
use Modules\CRM\Domain\Contracts\CrmLeadRepositoryContract;
use Modules\CRM\Domain\Entities\CrmLead;

/**
 * CRM lead repository implementation.
 *
 * Extends the tenant-aware AbstractRepository.
 * All queries are automatically scoped to the current tenant via HasTenant global scope.
 */
class CrmLeadRepository extends AbstractRepository implements CrmLeadRepositoryContract
{
    public function __construct()
    {
        $this->modelClass = CrmLead::class;
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
}
