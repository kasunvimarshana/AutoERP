<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Domain\Events\LeaveRequestCancelled;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class CancelLeaveRequestService extends BaseService implements CancelLeaveRequestServiceInterface
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

        $leaveRequest->cancel();

        $saved = $this->leaveRequestRepository->save($leaveRequest);
        $this->addEvent(new LeaveRequestCancelled($saved));

        return $saved;
    }
}
