<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\PickingTaskRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PickingTaskModel;

final class EloquentPickingTaskRepository extends EloquentRepository implements PickingTaskRepositoryInterface
{
    public function __construct(PickingTaskModel $model)
    {
        parent::__construct($model);
    }
}