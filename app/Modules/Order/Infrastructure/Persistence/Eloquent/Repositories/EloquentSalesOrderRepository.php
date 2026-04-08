<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Order\Domain\Contracts\Repositories\SalesOrderRepositoryInterface;
use Modules\Order\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;

class EloquentSalesOrderRepository extends EloquentRepository implements SalesOrderRepositoryInterface
{
    public function __construct(SalesOrderModel $model)
    {
        parent::__construct($model);
    }

    public function findByOrderNumber(string $number): mixed
    {
        return $this->model->newQuery()->where('order_number', $number)->first();
    }
}
