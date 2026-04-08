<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function getTree(int $tenantId): array;

    public function findBySlug(string $slug, int $tenantId): mixed;

    public function findRoots(int $tenantId): mixed;

    public function findChildren(int $parentId): mixed;
}
