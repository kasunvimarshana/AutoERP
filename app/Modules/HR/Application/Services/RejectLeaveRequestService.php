<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\RejectLeaveRequestServiceInterface;
use Modules\HR\Domain\Events\LeaveRequestRejected;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class RejectLeaveRequestService extends BaseService implements RejectLeaveRequestServiceInterface
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $leaveRequestRepository)
    {
        parent::__construct($leaveRequestRepository);
    }

    protected function handle(array $data): mixed
    {
        $id           = $data['id'];
        $leaveRequest = $this->leaveRequestRepository->find($id);
        if (! $leaveRequest) {
            throw new LeaveRequestNotFoundException($id);
        }

        $leaveRequest->reject((int) $data['approved_by'], $data['notes'] ?? null);

        $saved = $this->leaveRequestRepository->save($leaveRequest);
        $this->addEvent(new LeaveRequestRejected($saved));

        return $saved;
    }
}
