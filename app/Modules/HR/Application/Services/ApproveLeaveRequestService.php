<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\ApproveLeaveRequestServiceInterface;
use Modules\HR\Domain\Events\LeaveRequestApproved;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class ApproveLeaveRequestService extends BaseService implements ApproveLeaveRequestServiceInterface
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

        $leaveRequest->approve((int) $data['approved_by'], $data['notes'] ?? null);

        $saved = $this->leaveRequestRepository->save($leaveRequest);
        $this->addEvent(new LeaveRequestApproved($saved));

        return $saved;
    }
}
