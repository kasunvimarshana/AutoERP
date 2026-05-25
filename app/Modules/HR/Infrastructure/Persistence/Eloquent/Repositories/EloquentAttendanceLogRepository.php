<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\AttendanceLogRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceLogModel;

final class EloquentAttendanceLogRepository extends EloquentRepository implements AttendanceLogRepositoryInterface
{
    public function __construct(AttendanceLogModel $model)
    {
        parent::__construct($model);
    }
}