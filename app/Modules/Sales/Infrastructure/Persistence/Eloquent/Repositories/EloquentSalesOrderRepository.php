<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesOrderRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderModel;

final class EloquentSalesOrderRepository extends EloquentRepository implements SalesOrderRepositoryInterface
{
    public function __construct(SalesOrderModel $model)
    {
        parent::__construct($model);
    }
}