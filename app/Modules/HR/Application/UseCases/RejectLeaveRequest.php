<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases;

use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestRejected;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class RejectLeaveRequest
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $repo) {}

    public function execute(int $id, int $rejectedBy, ?string $notes = null): LeaveRequest
    {
        $leaveRequest = $this->repo->find($id);
        if (! $leaveRequest) {
            throw new LeaveRequestNotFoundException($id);
        }

        $leaveRequest->reject($rejectedBy, $notes);

        $saved = $this->repo->save($leaveRequest);
        LeaveRequestRejected::dispatch($saved);

        return $saved;
    }
}
