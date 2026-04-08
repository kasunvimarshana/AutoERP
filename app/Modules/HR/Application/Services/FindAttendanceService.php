<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\FindAttendanceServiceInterface;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

class FindAttendanceService extends BaseService implements FindAttendanceServiceInterface
{
    public function __construct(private readonly AttendanceRepositoryInterface $attendanceRepository)
    {
        parent::__construct($attendanceRepository);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\Attendance>
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->attendanceRepository->getByEmployee($employeeId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
