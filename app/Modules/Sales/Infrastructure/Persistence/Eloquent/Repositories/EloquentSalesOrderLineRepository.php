<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesOrderLineRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;

final class EloquentSalesOrderLineRepository extends EloquentRepository implements SalesOrderLineRepositoryInterface
{
    public function __construct(SalesOrderLineModel $model)
    {
        parent::__construct($model);
    }
}
