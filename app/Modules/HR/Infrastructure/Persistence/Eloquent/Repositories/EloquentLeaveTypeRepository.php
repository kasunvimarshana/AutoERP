<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\LeaveTypeRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveTypeModel;

final class EloquentLeaveTypeRepository extends EloquentRepository implements LeaveTypeRepositoryInterface
{
    public function __construct(LeaveTypeModel $model)
    {
        parent::__construct($model);
    }
}