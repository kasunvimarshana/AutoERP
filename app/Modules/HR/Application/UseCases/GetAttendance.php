<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\Attendance;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

class GetAttendance
{
    public function __construct(private readonly AttendanceRepositoryInterface $repo) {}

    public function execute(int $id): ?Attendance
    {
        return $this->repo->find($id);
    }
}
