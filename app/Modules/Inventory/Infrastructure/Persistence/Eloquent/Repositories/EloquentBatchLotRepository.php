<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\RepositoryInterfaces\BatchLotRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchLotModel;

final class EloquentBatchLotRepository extends EloquentRepository implements BatchLotRepositoryInterface
{
    public function __construct(BatchLotModel $model)
    {
        parent::__construct($model);
    }

    public function findByNumber(string $batchNumber, int $productId): mixed
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('batch_number', $batchNumber)
            ->where('product_id', $productId)
            ->first();
    }

    public function findByProduct(int $productId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('product_id', $productId)
            ->orderBy('expiry_date')
            ->get();
    }

    public function findExpiring(int $tenantId, \DateTimeInterface $before): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $before->format('Y-m-d'))
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get();
    }
}
