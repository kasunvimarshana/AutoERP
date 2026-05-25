<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\CycleCountLineRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountLineModel;

final class EloquentCycleCountLineRepository extends EloquentRepository implements CycleCountLineRepositoryInterface
{
    public function __construct(CycleCountLineModel $model)
    {
        parent::__construct($model);
    }
}