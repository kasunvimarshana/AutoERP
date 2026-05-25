<?php

declare(strict_types=1);

namespace Modules\HR\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Eloquent\Repositories\EloquentRepository;
use Modules\HR\Application\Repositories\AttendanceRecordRepositoryInterface;
use Modules\HR\Infrastructure\Persistence\Eloquent\Models\AttendanceRecordModel;

final class EloquentAttendanceRecordRepository extends EloquentRepository implements AttendanceRecordRepositoryInterface
{
    public function __construct(AttendanceRecordModel $model)
    {
        parent::__construct($model);
    }
}