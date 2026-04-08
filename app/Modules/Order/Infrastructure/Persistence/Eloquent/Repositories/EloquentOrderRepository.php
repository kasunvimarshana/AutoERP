<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Order\Domain\RepositoryInterfaces\OrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\OrderModel;

final class EloquentOrderRepository extends EloquentRepository implements OrderRepositoryInterface
{
    public function __construct(OrderModel $model)
    {
        parent::__construct($model);
    }

    public function findByReference(string $reference, int $tenantId): mixed
    {
        return $this->model->newQuery()
            ->where('reference_number', $reference)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findWithLines(int|string $id): mixed
    {
        return $this->model->newQuery()
            ->with('lines')
            ->find($id);
    }
}
