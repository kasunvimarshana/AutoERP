<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Product\Domain\Entities\Category;

interface CategoryRepositoryInterface
{
    public function findById(string $tenantId, string $id): ?Category;
    public function findBySlug(string $tenantId, string $slug): ?Category;
    /** @return Category[] */
    public function findAll(string $tenantId): array;
    /** @return Category[] */
    public function findChildren(string $tenantId, ?string $parentId): array;
    /** @return Category[] */
    public function findActive(string $tenantId): array;
    public function save(Category $category): void;
    public function delete(string $tenantId, string $id): void;
}
