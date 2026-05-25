<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\Inventory\Application\Repositories\TraceLogRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\TraceLogModel;

final class EloquentTraceLogRepository extends EloquentRepository implements TraceLogRepositoryInterface
{
    public function __construct(TraceLogModel $model)
    {
        parent::__construct($model);
    }
}