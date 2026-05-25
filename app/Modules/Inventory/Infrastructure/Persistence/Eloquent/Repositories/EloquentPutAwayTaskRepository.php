<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\PutAwayTaskRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\PutAwayTaskModel;

final class EloquentPutAwayTaskRepository extends EloquentRepository implements PutAwayTaskRepositoryInterface
{
    public function __construct(PutAwayTaskModel $model)
    {
        parent::__construct($model);
    }
}