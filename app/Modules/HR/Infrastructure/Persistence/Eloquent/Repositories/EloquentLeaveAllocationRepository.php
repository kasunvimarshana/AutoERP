<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\LeaveAllocationRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveAllocationModel;

final class EloquentLeaveAllocationRepository extends EloquentRepository implements LeaveAllocationRepositoryInterface
{
    public function __construct(LeaveAllocationModel $model)
    {
        parent::__construct($model);
    }
}