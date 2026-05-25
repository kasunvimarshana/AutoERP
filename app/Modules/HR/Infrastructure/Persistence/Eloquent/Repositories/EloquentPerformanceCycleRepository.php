<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\PerformanceCycleRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\PerformanceCycleModel;

final class EloquentPerformanceCycleRepository extends EloquentRepository implements PerformanceCycleRepositoryInterface
{
    public function __construct(PerformanceCycleModel $model)
    {
        parent::__construct($model);
    }
}