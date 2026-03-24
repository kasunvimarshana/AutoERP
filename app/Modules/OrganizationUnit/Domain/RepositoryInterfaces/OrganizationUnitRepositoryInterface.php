<?php

namespace Modules\OrganizationUnit\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\OrganizationUnit\Domain\Entities\OrganizationUnit;

interface OrganizationUnitRepositoryInterface extends RepositoryInterface
{
    public function save(OrganizationUnit $unit): OrganizationUnit;
    public function getTree(int $tenantId, ?int $rootId = null): array;
    public function getDescendants(int $id): array;
    public function getAncestors(int $id): array;
    public function moveNode(int $id, ?int $newParentId): void;
    public function rebuildTree(): void;
}
