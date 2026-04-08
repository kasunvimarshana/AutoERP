<?php

declare(strict_types=1);

namespace Modules\Warehouse\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface OrganizationUnitRepositoryInterface extends RepositoryInterface
{
    public function findRoots(): Collection;
    public function findChildren(string $parentId): Collection;
}
