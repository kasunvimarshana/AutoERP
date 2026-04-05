<?php
declare(strict_types=1);
namespace Modules\Product\Domain\RepositoryInterfaces;

use Modules\Product\Domain\Entities\ProductComponent;

interface ProductComponentRepositoryInterface
{
    public function findById(int $id): ?ProductComponent;
    public function findByParent(int $tenantId, int $parentProductId): array;
    public function create(array $data): ProductComponent;
    public function update(int $id, array $data): ?ProductComponent;
    public function delete(int $id): bool;
    public function deleteByParent(int $tenantId, int $parentProductId): int;
}
