<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\StockMovementRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockMovementModel;

final class EloquentStockMovementRepository extends EloquentRepository implements StockMovementRepositoryInterface
{
    public function __construct(StockMovementModel $model)
    {
        parent::__construct($model);
    }
}