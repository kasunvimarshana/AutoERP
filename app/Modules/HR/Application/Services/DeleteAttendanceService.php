<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteAttendanceServiceInterface;
use Modules\HR\Domain\Events\AttendanceDeleted;
use Modules\HR\Domain\Exceptions\AttendanceNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

class DeleteAttendanceService extends BaseService implements DeleteAttendanceServiceInterface
{
    public function __construct(private readonly AttendanceRepositoryInterface $attendanceRepository)
    {
        parent::__construct($attendanceRepository);
    }

    protected function handle(array $data): bool
    {
        $id         = $data['id'];
        $attendance = $this->attendanceRepository->find($id);
        if (! $attendance) {
            throw new AttendanceNotFoundException($id);
        }

        $tenantId = $attendance->getTenantId();
        $deleted  = $this->attendanceRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new AttendanceDeleted($tenantId, $id));
        }

        return $deleted;
    }
}
