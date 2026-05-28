<?php

declare(strict_types=1);

namespace Modules\Sales\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Sales\Application\Repositories\SalesStatusHistoryRepositoryInterface;
use Modules\Sales\Infrastructure\Persistence\Eloquent\Models\SalesStatusHistoryModel;

final class EloquentSalesStatusHistoryRepository extends EloquentRepository implements SalesStatusHistoryRepositoryInterface
{
    public function __construct(SalesStatusHistoryModel $model)
    {
        parent::__construct($model);
    }
}
