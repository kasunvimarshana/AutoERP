<?php declare(strict_types=1);

namespace Modules\Product\Application\Contracts;

use Modules\Product\Domain\Entities\ProductCategory;

interface ManageProductCategoryServiceInterface
{
    public function create(array $data): ProductCategory;
    public function find(int $tenantId, string $id): ProductCategory;
    public function list(int $tenantId, array $filters = []): array;
    public function update(int $tenantId, string $id, array $data): ProductCategory;
    public function delete(int $tenantId, string $id): void;
}
