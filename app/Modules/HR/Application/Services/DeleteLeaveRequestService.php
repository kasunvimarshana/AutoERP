<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\DeleteLeaveRequestServiceInterface;
use Modules\HR\Domain\Events\LeaveRequestDeleted;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class DeleteLeaveRequestService extends BaseService implements DeleteLeaveRequestServiceInterface
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $leaveRequestRepository)
    {
        parent::__construct($leaveRequestRepository);
    }

    protected function handle(array $data): bool
    {
        $id           = $data['id'];
        $leaveRequest = $this->leaveRequestRepository->find($id);
        if (! $leaveRequest) {
            throw new LeaveRequestNotFoundException($id);
        }
        $tenantId = $leaveRequest->getTenantId();
        $deleted  = $this->leaveRequestRepository->delete($id);
        if ($deleted) {
            $this->addEvent(new LeaveRequestDeleted($id, $tenantId));
        }

        return $deleted;
    }
}
