<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\CycleCountHeaderRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\CycleCountHeaderModel;

final class EloquentCycleCountHeaderRepository extends EloquentRepository implements CycleCountHeaderRepositoryInterface
{
    public function __construct(CycleCountHeaderModel $model)
    {
        parent::__construct($model);
    }
}