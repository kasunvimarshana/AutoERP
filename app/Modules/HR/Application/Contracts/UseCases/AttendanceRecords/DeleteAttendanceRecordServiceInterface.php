<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\AttendanceRecords;

use Modules\Core\Application\Results\Result;

interface DeleteAttendanceRecordServiceInterface
{
    public function execute(int|string $id): Result;
}