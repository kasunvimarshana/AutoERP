<?php

declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\Category;

interface CategoryServiceInterface
{
    public function getCategory(string $tenantId, string $id): Category;
    public function createCategory(string $tenantId, array $data): Category;
    public function updateCategory(string $tenantId, string $id, array $data): Category;
    public function deleteCategory(string $tenantId, string $id): void;
    /** @return Category[] */
    public function getAllCategories(string $tenantId): array;
    public function getCategoryTree(string $tenantId): array;
}
