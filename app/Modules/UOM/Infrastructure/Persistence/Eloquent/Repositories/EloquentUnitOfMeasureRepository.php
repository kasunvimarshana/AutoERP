<?php

declare(strict_types=1);

namespace Modules\UOM\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Infrastructure\Persistence\Eloquent\Models\UnitOfMeasureModel;

final class EloquentUnitOfMeasureRepository extends EloquentRepository implements UnitOfMeasureRepositoryInterface
{
    public function __construct(UnitOfMeasureModel $model)
    {
        parent::__construct($model);
    }
}