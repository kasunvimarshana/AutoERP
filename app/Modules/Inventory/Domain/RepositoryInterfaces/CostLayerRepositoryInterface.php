<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\RepositoryInterfaces;

use Modules\Inventory\Domain\Entities\InventoryCostLayer;

interface CostLayerRepositoryInterface
{
    /**
     * Persist a new cost layer and return it with its assigned id.
     */
    public function create(InventoryCostLayer $layer): InventoryCostLayer;

    /**
     * Persist changes to an existing cost layer (quantity_remaining, unit_cost, is_closed).
     */
    public function update(InventoryCostLayer $layer): InventoryCostLayer;

    /**
     * Return all open (is_closed = false) layers for the given product/variant/location,
     * ordered by layer_date ascending (oldest first — suitable for FIFO/FEFO selection).
     *
     * @return InventoryCostLayer[]
     */
    public function findOpenLayersOldestFirst(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId = null,
    ): array;

    /**
     * Return all open layers ordered by layer_date descending (newest first — LIFO).
     *
     * @return InventoryCostLayer[]
     */
    public function findOpenLayersNewestFirst(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId = null,
    ): array;

    /**
     * Return all open layers ordered by the batch expiry_date ascending (FEFO).
     * Layers without a batch (or batch without expiry) are ordered last.
     *
     * @return InventoryCostLayer[]
     */
    public function findOpenLayersByExpiryAsc(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId = null,
    ): array;

    /**
     * Return all open layers for a product/location regardless of order.
     *
     * @return InventoryCostLayer[]
     */
    public function findAllOpenLayers(
        int $tenantId,
        int $productId,
        int $locationId,
        ?int $variantId = null,
    ): array;

    /**
     * Find a specific cost layer by its id within a tenant.
     */
    public function findById(int $tenantId, int $id): ?InventoryCostLayer;
}
