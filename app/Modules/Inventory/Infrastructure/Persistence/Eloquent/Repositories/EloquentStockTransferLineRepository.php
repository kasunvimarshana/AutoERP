<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\StockTransferLineRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferLineModel;

final class EloquentStockTransferLineRepository extends EloquentRepository implements StockTransferLineRepositoryInterface
{
    public function __construct(StockTransferLineModel $model)
    {
        parent::__construct($model);
    }
}