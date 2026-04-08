<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\RepositoryInterfaces\StockItemRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockItemModel;

final class EloquentStockItemRepository extends EloquentRepository implements StockItemRepositoryInterface
{
    public function __construct(StockItemModel $model)
    {
        parent::__construct($model);
    }

    public function findByProductAndLocation(int $productId, int $locationId, ?int $variantId = null): mixed
    {
        $query = $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('product_id', $productId)
            ->where('location_id', $locationId);

        if ($variantId !== null) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        return $query->first();
    }

    public function findByProduct(int $productId, int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function findByLocation(int $locationId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('location_id', $locationId)
            ->get();
    }

    public function decrementQuantity(int $id, float $qty): void
    {
        $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('id', $id)
            ->decrement('quantity_on_hand', $qty);

        $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('id', $id)
            ->decrement('quantity_available', $qty);
    }

    public function incrementQuantity(int $id, float $qty): void
    {
        $abs = abs($qty);

        if ($qty >= 0) {
            $this->model->newQuery()
                ->withoutGlobalScope('tenant')
                ->where('id', $id)
                ->increment('quantity_on_hand', $abs);

            $this->model->newQuery()
                ->withoutGlobalScope('tenant')
                ->where('id', $id)
                ->increment('quantity_available', $abs);
        } else {
            $this->decrementQuantity($id, $abs);
        }
    }
}
