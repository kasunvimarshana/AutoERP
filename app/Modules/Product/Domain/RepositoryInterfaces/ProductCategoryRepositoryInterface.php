<?php
namespace Modules\Product\Domain\RepositoryInterfaces;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Domain\Entities\ProductCategory;

interface ProductCategoryRepositoryInterface
{
    public function findById(int $id): ?ProductCategory;
    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $data): ProductCategory;
    public function update(ProductCategory $category, array $data): ProductCategory;
    public function delete(ProductCategory $category): bool;
}
