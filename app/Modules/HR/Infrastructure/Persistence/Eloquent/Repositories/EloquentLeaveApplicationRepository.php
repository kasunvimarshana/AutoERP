<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\LeaveApplicationRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\LeaveApplicationModel;

final class EloquentLeaveApplicationRepository extends EloquentRepository implements LeaveApplicationRepositoryInterface
{
    public function __construct(LeaveApplicationModel $model)
    {
        parent::__construct($model);
    }
}