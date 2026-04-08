<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\RepositoryInterfaces\SerialNumberRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\SerialNumberModel;

final class EloquentSerialNumberRepository extends EloquentRepository implements SerialNumberRepositoryInterface
{
    public function __construct(SerialNumberModel $model)
    {
        parent::__construct($model);
    }

    public function findBySerial(string $serialNumber, int $productId): mixed
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('serial_number', $serialNumber)
            ->where('product_id', $productId)
            ->first();
    }

    public function findByProduct(int $productId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('product_id', $productId)
            ->orderBy('serial_number')
            ->get();
    }

    public function findAvailableByProduct(int $productId): Collection
    {
        return $this->model->newQuery()
            ->withoutGlobalScope('tenant')
            ->where('product_id', $productId)
            ->where('status', 'available')
            ->orderBy('serial_number')
            ->get();
    }
}
