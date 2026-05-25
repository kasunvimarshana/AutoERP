<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesReturnLineRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnLineModel;

final class EloquentSalesReturnLineRepository extends EloquentRepository implements SalesReturnLineRepositoryInterface
{
    public function __construct(SalesReturnLineModel $model)
    {
        parent::__construct($model);
    }
}