<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\StockTransferRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockTransferModel;

final class EloquentStockTransferRepository extends EloquentRepository implements StockTransferRepositoryInterface
{
    public function __construct(StockTransferModel $model)
    {
        parent::__construct($model);
    }
}