<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\AttendanceLogs;

use Modules\Core\Application\Results\Result;

interface ListAttendanceLogsServiceInterface
{
    /**
     * @param array<string, mixed> $criteria
     */
    public function execute(array $criteria, int $perPage, int $page): Result;
}