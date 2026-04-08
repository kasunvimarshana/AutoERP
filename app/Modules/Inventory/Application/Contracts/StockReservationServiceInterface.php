<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Application\Contracts\ServiceInterface;

interface StockReservationServiceInterface extends ServiceInterface
{
    /**
     * Reserve stock for a product in a warehouse, linked to an optional reference.
     * Updates the inventory item's quantity_reserved and quantity_available atomically.
     *
     * @throws \Modules\Inventory\Domain\Exceptions\InsufficientStockException
     */
    public function reserve(
        int $tenantId,
        string $productId,
        string $warehouseId,
        float $quantity,
        ?string $variantId = null,
        ?string $locationId = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $expiresAt = null,
    ): mixed;

    /**
     * Release (cancel) a specific reservation.
     * Restores quantity_reserved and quantity_available on the inventory item.
     */
    public function release(string $reservationId): mixed;

    /**
     * Fulfil a reservation (transition status to 'fulfilled') without restoring quantities.
     * Called when stock is actually shipped / consumed.
     */
    public function fulfil(string $reservationId): mixed;

    /**
     * Release all expired active reservations for a tenant.
     * Returns the count of reservations released.
     */
    public function releaseExpired(int $tenantId): int;

    /**
     * Return total reserved quantity for a product in a warehouse.
     */
    public function getTotalReserved(string $productId, string $warehouseId, ?string $variantId = null): float;

    /**
     * Return all active reservations linked to a reference (e.g., order_id).
     */
    public function findByReference(string $referenceType, string $referenceId): Collection;
}
