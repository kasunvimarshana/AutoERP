<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\StockReservationRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\StockReservationModel;

final class EloquentStockReservationRepository extends EloquentRepository implements StockReservationRepositoryInterface
{
    public function __construct(StockReservationModel $model)
    {
        parent::__construct($model);
    }
}