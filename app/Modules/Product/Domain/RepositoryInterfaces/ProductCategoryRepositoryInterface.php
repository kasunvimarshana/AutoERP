<?php

declare(strict_types=1);

namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Domain\Entities\ProductCategory;

interface ProductCategoryRepositoryInterface
{
    public function findById(int $id): ?ProductCategory;
    public function findBySlug(int $tenantId, string $slug): ?ProductCategory;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function findByParent(int $tenantId, ?int $parentId): array;
    public function create(array $data): ProductCategory;
    public function update(int $id, array $data): ?ProductCategory;
    public function delete(int $id): bool;
    public function buildTree(int $tenantId): array;
}
