<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\AttendanceLogs;

use Modules\Core\Application\Results\Result;

interface CreateAttendanceLogServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(array $payload): Result;
}