<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\StockAdjustmentLineRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentLineModel;

final class EloquentStockAdjustmentLineRepository extends EloquentRepository implements StockAdjustmentLineRepositoryInterface
{
    public function __construct(StockAdjustmentLineModel $model)
    {
        parent::__construct($model);
    }
}