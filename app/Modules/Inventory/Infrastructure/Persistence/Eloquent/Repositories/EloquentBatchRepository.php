<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\BatchRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\BatchModel;

final class EloquentBatchRepository extends EloquentRepository implements BatchRepositoryInterface
{
    public function __construct(BatchModel $model)
    {
        parent::__construct($model);
    }
}