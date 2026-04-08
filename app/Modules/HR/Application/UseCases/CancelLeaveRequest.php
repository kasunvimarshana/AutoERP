<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestCancelled;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class CancelLeaveRequest
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $repo) {}

    public function execute(int $id): LeaveRequest
    {
        $leaveRequest = $this->repo->find($id);
        if (! $leaveRequest) {
            throw new LeaveRequestNotFoundException($id);
        }

        $leaveRequest->cancel();

        $saved = $this->repo->save($leaveRequest);
        LeaveRequestCancelled::dispatch($saved);

        return $saved;
    }
}
