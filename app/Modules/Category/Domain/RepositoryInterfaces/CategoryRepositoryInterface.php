<?php

declare(strict_types=1);

namespace Modules\Category\Domain\RepositoryInterfaces;

use Illuminate\Support\Collection;
use Modules\Category\Domain\Entities\Category;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface CategoryRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(int $tenantId, string $slug): ?Category;

    public function findByTenant(int $tenantId): Collection;

    public function findChildren(int $parentId): Collection;

    public function findRoots(int $tenantId): Collection;

    public function getTree(int $tenantId, ?int $rootId = null): Collection;

    public function getDescendants(int $id): Collection;

    public function save(Category $category): Category;
}
