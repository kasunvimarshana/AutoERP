<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Order\Domain\Contracts\Repositories\PurchaseOrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;

class EloquentPurchaseOrderRepository extends EloquentRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(PurchaseOrderModel $model)
    {
        parent::__construct($model);
    }

    public function findByOrderNumber(string $number): mixed
    {
        return $this->model->newQuery()->where('order_number', $number)->first();
    }
}
