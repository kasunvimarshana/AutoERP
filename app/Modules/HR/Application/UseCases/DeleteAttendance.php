<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Events\AttendanceDeleted;
use Modules\HR\Domain\Exceptions\AttendanceNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\AttendanceRepositoryInterface;

class DeleteAttendance
{
    public function __construct(private readonly AttendanceRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $attendance = $this->repo->find($id);
        if (! $attendance) {
            throw new AttendanceNotFoundException($id);
        }

        $tenantId = $attendance->getTenantId();
        $deleted  = $this->repo->delete($id);
        if ($deleted) {
            AttendanceDeleted::dispatch($tenantId, $id);
        }

        return $deleted;
    }
}
