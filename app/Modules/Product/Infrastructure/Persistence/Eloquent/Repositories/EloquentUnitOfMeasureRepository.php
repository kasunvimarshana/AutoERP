<?php

declare(strict_types=1);

namespace Modules\Product\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Product\Domain\Contracts\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\Product\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

class EloquentUnitOfMeasureRepository extends EloquentRepository implements UnitOfMeasureRepositoryInterface
{
    public function __construct(UnitOfMeasureModel $model)
    {
        parent::__construct($model);
    }
}
