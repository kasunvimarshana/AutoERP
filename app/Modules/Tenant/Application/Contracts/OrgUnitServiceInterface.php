<?php

declare(strict_types=1);

namespace Modules\Tenant\Application\Contracts;

use Modules\Tenant\Application\DTOs\OrgUnitData;

interface OrgUnitServiceInterface
{
    /**
     * Create a new org unit from the given DTO.
     */
    public function create(OrgUnitData $dto): mixed;

    /**
     * Return a hierarchical tree of org units for the given tenant.
     */
    public function getTree(int $tenantId): array;

    /**
     * Move an org unit to a new parent (or to the root when $parentId is null).
     */
    public function move(int $id, ?int $parentId): mixed;
}
