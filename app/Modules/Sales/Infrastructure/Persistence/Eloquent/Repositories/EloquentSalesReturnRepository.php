<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesReturnRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesReturnModel;

final class EloquentSalesReturnRepository extends EloquentRepository implements SalesReturnRepositoryInterface
{
    public function __construct(SalesReturnModel $model)
    {
        parent::__construct($model);
    }
}