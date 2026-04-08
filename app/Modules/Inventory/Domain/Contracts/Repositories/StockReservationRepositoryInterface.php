<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\Contracts\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Domain\Contracts\Repositories\RepositoryInterface;

interface StockReservationRepositoryInterface extends RepositoryInterface
{
    /**
     * Find all active reservations for a product + warehouse combination.
     */
    public function findActive(string $productId, string $warehouseId, ?string $variantId = null): Collection;

    /**
     * Find reservations by reference (e.g., sales_order, purchase_order).
     */
    public function findByReference(string $referenceType, string $referenceId): Collection;

    /**
     * Calculate the total reserved quantity for a product in a warehouse.
     */
    public function getTotalReserved(string $productId, string $warehouseId, ?string $variantId = null): float;

    /**
     * Find all active reservations that have expired (expires_at < now) for a tenant.
     */
    public function findExpired(int $tenantId): Collection;
}
