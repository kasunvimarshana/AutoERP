<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\BatcheRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatcheModel;

final class EloquentBatcheRepository extends EloquentRepository implements BatcheRepositoryInterface
{
    public function __construct(BatcheModel $model)
    {
        parent::__construct($model);
    }
}