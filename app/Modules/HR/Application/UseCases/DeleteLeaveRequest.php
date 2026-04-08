<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Events\LeaveRequestDeleted;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class DeleteLeaveRequest
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $repo) {}

    public function execute(int $id): bool
    {
        $leaveRequest = $this->repo->find($id);
        if (! $leaveRequest) {
            throw new LeaveRequestNotFoundException($id);
        }

        $tenantId = $leaveRequest->getTenantId();
        $deleted  = $this->repo->delete($id);
        if ($deleted) {
            LeaveRequestDeleted::dispatch($id, $tenantId);
        }

        return $deleted;
    }
}
