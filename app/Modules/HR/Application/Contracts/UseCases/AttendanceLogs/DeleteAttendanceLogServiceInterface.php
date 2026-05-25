<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\AttendanceLogs;

use Modules\Core\Application\Results\Result;

interface DeleteAttendanceLogServiceInterface
{
    public function execute(int|string $id): Result;
}