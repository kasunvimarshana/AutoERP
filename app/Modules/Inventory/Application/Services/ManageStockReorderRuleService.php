<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Application\Contracts\ManageStockReorderRuleServiceInterface;
use Modules\Inventory\Domain\Entities\StockReorderRule;
use Modules\Inventory\Domain\RepositoryInterfaces\StockReorderRuleRepositoryInterface;

class ManageStockReorderRuleService implements ManageStockReorderRuleServiceInterface
{
    public function __construct(
        private readonly StockReorderRuleRepositoryInterface $repository,
    ) {}

    public function create(array $data): StockReorderRule
    {
        return DB::transaction(function () use ($data): StockReorderRule {
            $rule = new StockReorderRule(
                tenantId: (int) $data['tenant_id'],
                productId: (int) $data['product_id'],
                variantId: isset($data['variant_id']) ? (int) $data['variant_id'] : null,
                warehouseId: (int) $data['warehouse_id'],
                minimumQuantity: (string) $data['minimum_quantity'],
                maximumQuantity: isset($data['maximum_quantity']) ? (string) $data['maximum_quantity'] : null,
                reorderQuantity: (string) $data['reorder_quantity'],
                isActive: (bool) ($data['is_active'] ?? true),
            );

            return $this->repository->create($rule);
        });
    }

    public function update(int $tenantId, int $id, array $data): StockReorderRule
    {
        return DB::transaction(function () use ($tenantId, $id, $data): StockReorderRule {
            $existing = $this->repository->findById($tenantId, $id);

            $updated = new StockReorderRule(
                tenantId: $tenantId,
                productId: isset($data['product_id']) ? (int) $data['product_id'] : $existing->getProductId(),
                variantId: array_key_exists('variant_id', $data) ? (isset($data['variant_id']) ? (int) $data['variant_id'] : null) : $existing->getVariantId(),
                warehouseId: isset($data['warehouse_id']) ? (int) $data['warehouse_id'] : $existing->getWarehouseId(),
                minimumQuantity: isset($data['minimum_quantity']) ? (string) $data['minimum_quantity'] : $existing->getMinimumQuantity(),
                maximumQuantity: array_key_exists('maximum_quantity', $data) ? (isset($data['maximum_quantity']) ? (string) $data['maximum_quantity'] : null) : $existing->getMaximumQuantity(),
                reorderQuantity: isset($data['reorder_quantity']) ? (string) $data['reorder_quantity'] : $existing->getReorderQuantity(),
                isActive: isset($data['is_active']) ? (bool) $data['is_active'] : $existing->isActive(),
                id: $id,
            );

            return $this->repository->update($updated);
        });
    }

    public function delete(int $tenantId, int $id): void
    {
        DB::transaction(function () use ($tenantId, $id): void {
            $this->repository->delete($tenantId, $id);
        });
    }

    public function find(int $tenantId, int $id): StockReorderRule
    {
        return $this->repository->findById($tenantId, $id);
    }

    public function list(int $tenantId, int $perPage = 15, int $page = 1): mixed
    {
        return $this->repository->listByTenant($tenantId, $perPage, $page);
    }

    public function listLowStock(int $tenantId): array
    {
        return $this->repository->listBelowMinimum($tenantId);
    }
}
