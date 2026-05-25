<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\StockAdjustmentRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockAdjustmentModel;

final class EloquentStockAdjustmentRepository extends EloquentRepository implements StockAdjustmentRepositoryInterface
{
    public function __construct(StockAdjustmentModel $model)
    {
        parent::__construct($model);
    }
}