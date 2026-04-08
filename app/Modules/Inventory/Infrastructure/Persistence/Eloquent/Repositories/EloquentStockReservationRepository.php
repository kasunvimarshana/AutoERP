<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Contracts\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;

class EloquentStockReservationRepository extends EloquentRepository implements StockReservationRepositoryInterface
{
    public function __construct(StockReservationModel $model)
    {
        parent::__construct($model);
    }

    /**
     * Find all active reservations for a product in a warehouse.
     */
    public function findActive(string $productId, string $warehouseId, ?string $variantId = null): Collection
    {
        $query = $this->model->newQuery()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active');

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        }

        return $query->get();
    }

    /**
     * Find reservations by reference type and ID.
     */
    public function findByReference(string $referenceType, string $referenceId): Collection
    {
        return $this->model->newQuery()
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get();
    }

    /**
     * Calculate the total reserved quantity for a product in a warehouse.
     */
    public function getTotalReserved(string $productId, string $warehouseId, ?string $variantId = null): float
    {
        $query = $this->model->newQuery()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'active');

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Find all active reservations that have expired (expires_at < now) for a tenant.
     */
    public function findExpired(int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();
    }
}
