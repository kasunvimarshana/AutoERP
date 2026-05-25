<?php

declare(strict_types=1);

namespace Modules\HR\Application\Contracts\UseCases\AttendanceRecords;

use Modules\Core\Application\Results\Result;

interface UpdateAttendanceRecordServiceInterface
{
    /**
     * @param array<string, mixed> $payload
     */
    public function execute(int|string $id, array $payload): Result;
}