<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Product\Domain\Entities\ProductCategory;

interface ProductCategoryServiceInterface
{
    public function findById(int $id): ProductCategory;
    public function findByTenant(int $tenantId, int $perPage = 15, int $page = 1): LengthAwarePaginator;
    public function getTree(int $tenantId): array;
    public function create(array $data): ProductCategory;
    public function update(int $id, array $data): ProductCategory;
    public function delete(int $id): bool;
}
