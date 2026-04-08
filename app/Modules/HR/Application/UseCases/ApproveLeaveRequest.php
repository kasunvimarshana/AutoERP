<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestApproved;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class ApproveLeaveRequest
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $repo) {}

    public function execute(int $id, int $approvedBy, ?string $notes = null): LeaveRequest
    {
        $leaveRequest = $this->repo->find($id);
        if (! $leaveRequest) {
            throw new LeaveRequestNotFoundException($id);
        }

        $leaveRequest->approve($approvedBy, $notes);

        $saved = $this->repo->save($leaveRequest);
        LeaveRequestApproved::dispatch($saved);

        return $saved;
    }
}
