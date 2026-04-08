<?php

declare(strict_types=1);

namespace Modules\Tenant\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface OrgUnitRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all org units belonging to a tenant.
     */
    public function findByTenant(int $tenantId): Collection;

    /**
     * Get direct children of a given org unit.
     */
    public function findChildren(int $parentId): Collection;

    /**
     * Build a hierarchical tree of org units for a tenant.
     */
    public function getTree(int $tenantId): array;
}
