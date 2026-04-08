<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\RepositoryInterfaces\StockMovementRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;

final class EloquentStockMovementRepository extends EloquentRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovementModel $model)
    {
        parent::__construct($model);
    }

    public function findByProduct(int $productId, int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('moved_at')
            ->get();
    }

    public function findByReference(string $reference, int $tenantId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('reference', $reference)
            ->where('tenant_id', $tenantId)
            ->orderByDesc('moved_at')
            ->get();
    }
}
