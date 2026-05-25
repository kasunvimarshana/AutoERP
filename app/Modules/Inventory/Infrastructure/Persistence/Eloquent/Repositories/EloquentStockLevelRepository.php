<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\StockLevelRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockLevelModel;

final class EloquentStockLevelRepository extends EloquentRepository implements StockLevelRepositoryInterface
{
    public function __construct(StockLevelModel $model)
    {
        parent::__construct($model);
    }
}