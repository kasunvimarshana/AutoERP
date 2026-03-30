<?php

declare(strict_types=1);

namespace Modules\Location\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;
use Modules\Location\Domain\Entities\Location;

interface LocationRepositoryInterface extends RepositoryInterface
{
    public function save(Location $location): Location;

    public function getTree(int $tenantId, ?int $rootId = null): array;

    public function getDescendants(int $id): array;

    public function getAncestors(int $id): array;

    public function moveNode(int $id, ?int $newParentId): void;

    public function rebuildTree(): void;
}
