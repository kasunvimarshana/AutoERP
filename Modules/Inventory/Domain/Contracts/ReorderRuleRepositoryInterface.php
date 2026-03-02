<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts;

use Modules\Inventory\Domain\Entities\ReorderRule;

interface ReorderRuleRepositoryInterface
{
    public function save(ReorderRule $rule): ReorderRule;

    public function findById(int $tenantId, int $id): ?ReorderRule;

    public function existsForProductAndWarehouse(int $tenantId, int $productId, int $warehouseId): bool;

    public function findAll(
        int $tenantId,
        ?int $productId,
        ?int $warehouseId,
        bool $activeOnly,
        int $page,
        int $perPage
    ): array;

    public function delete(int $tenantId, int $id): void;

    /**
     * Returns items where stock balance quantity_on_hand <= reorder_point
     * (or quantity_on_hand IS NULL meaning no stock at all).
     * Each item: ['rule' => ReorderRule, 'quantity_on_hand' => string]
     */
    public function findLowStockItems(
        int $tenantId,
        ?int $warehouseId,
        int $page,
        int $perPage
    ): array;
}
