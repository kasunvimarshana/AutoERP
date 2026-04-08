<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Application\Services\BaseService;
use Modules\Inventory\Application\Contracts\StockReservationServiceInterface;
use Modules\Inventory\Domain\Contracts\Repositories\InventoryItemRepositoryInterface;
use Modules\Inventory\Domain\Contracts\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Domain\Exceptions\InsufficientStockException;

class StockReservationService extends BaseService implements StockReservationServiceInterface
{
    public function __construct(
        StockReservationRepositoryInterface $repository,
        private readonly InventoryItemRepositoryInterface $inventoryItemRepository,
    ) {
        parent::__construct($repository);
    }

    /**
     * Default execute handler.
     */
    protected function handle(array $data): mixed
    {
        return $this->reserve(
            (int) $data['tenant_id'],
            $data['product_id'],
            $data['warehouse_id'],
            (float) $data['quantity'],
            $data['variant_id'] ?? null,
            $data['location_id'] ?? null,
            $data['reference_type'] ?? null,
            $data['reference_id'] ?? null,
            $data['expires_at'] ?? null,
        );
    }

    /**
     * Reserve stock for a product in a warehouse.
     *
     * @throws InsufficientStockException
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
    ): mixed {
        return DB::transaction(function () use (
            $tenantId, $productId, $warehouseId, $quantity,
            $variantId, $locationId, $referenceType, $referenceId, $expiresAt
        ) {
            $item = $this->inventoryItemRepository->findByProductWarehouse(
                $productId, $warehouseId, $variantId,
            );

            $available = $item ? (float) $item->quantity_available : 0.0;

            if ($available < $quantity) {
                throw new InsufficientStockException($productId, $quantity, $available);
            }

            // Create the reservation record
            /** @var StockReservationRepositoryInterface $repo */
            $repo = $this->repository;
            $reservation = $repo->create([
                'tenant_id'      => $tenantId,
                'product_id'     => $productId,
                'variant_id'     => $variantId,
                'warehouse_id'   => $warehouseId,
                'location_id'    => $locationId,
                'quantity'       => $quantity,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'status'         => 'active',
                'expires_at'     => $expiresAt,
            ]);

            // Atomically update inventory item quantities
            if ($item) {
                $this->inventoryItemRepository->update($item->id, [
                    'quantity_reserved'  => (float) $item->quantity_reserved + $quantity,
                    'quantity_available' => (float) $item->quantity_available - $quantity,
                ]);
            }

            return $reservation;
        });
    }

    /**
     * Release (cancel) a reservation and restore inventory quantities.
     */
    public function release(string $reservationId): mixed
    {
        return DB::transaction(function () use ($reservationId) {
            /** @var StockReservationRepositoryInterface $repo */
            $repo = $this->repository;
            $reservation = $repo->find($reservationId);

            if (! $reservation || $reservation->status !== 'active') {
                return null;
            }

            $result = $repo->update($reservationId, ['status' => 'cancelled']);

            $item = $this->inventoryItemRepository->findByProductWarehouse(
                $reservation->product_id,
                $reservation->warehouse_id,
                $reservation->variant_id,
            );

            if ($item) {
                $qty = (float) $reservation->quantity;
                $this->inventoryItemRepository->update($item->id, [
                    'quantity_reserved'  => max(0.0, (float) $item->quantity_reserved - $qty),
                    'quantity_available' => (float) $item->quantity_available + $qty,
                ]);
            }

            return $result;
        });
    }

    /**
     * Fulfil a reservation (mark as fulfilled — stock was consumed/shipped).
     * Does NOT restore quantities since stock was actually used.
     */
    public function fulfil(string $reservationId): mixed
    {
        return DB::transaction(function () use ($reservationId) {
            /** @var StockReservationRepositoryInterface $repo */
            $repo = $this->repository;
            $reservation = $repo->find($reservationId);

            if (! $reservation || $reservation->status !== 'active') {
                return null;
            }

            // quantity_available was already reduced at reserve time; just reduce reserved counter
            $item = $this->inventoryItemRepository->findByProductWarehouse(
                $reservation->product_id,
                $reservation->warehouse_id,
                $reservation->variant_id,
            );

            if ($item) {
                $qty = (float) $reservation->quantity;
                $this->inventoryItemRepository->update($item->id, [
                    'quantity_reserved' => max(0.0, (float) $item->quantity_reserved - $qty),
                    'quantity_on_hand'  => max(0.0, (float) $item->quantity_on_hand - $qty),
                ]);
            }

            return $repo->update($reservationId, ['status' => 'fulfilled']);
        });
    }

    /**
     * Release all expired active reservations for a tenant.
     *
     * @return int Number of reservations released.
     */
    public function releaseExpired(int $tenantId): int
    {
        /** @var StockReservationRepositoryInterface $repo */
        $repo    = $this->repository;
        $expired = $repo->findExpired($tenantId);

        $count = 0;
        foreach ($expired as $reservation) {
            $this->release($reservation->id);
            $count++;
        }

        return $count;
    }

    /**
     * Return total reserved quantity for a product in a warehouse.
     */
    public function getTotalReserved(string $productId, string $warehouseId, ?string $variantId = null): float
    {
        /** @var StockReservationRepositoryInterface $repo */
        $repo = $this->repository;

        return $repo->getTotalReserved($productId, $warehouseId, $variantId);
    }

    /**
     * Return all active reservations linked to a reference (e.g., order_id).
     */
    public function findByReference(string $referenceType, string $referenceId): Collection
    {
        /** @var StockReservationRepositoryInterface $repo */
        $repo = $this->repository;

        return $repo->findByReference($referenceType, $referenceId);
    }
}
