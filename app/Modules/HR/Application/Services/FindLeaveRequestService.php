<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\FindLeaveRequestServiceInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;

class FindLeaveRequestService extends BaseService implements FindLeaveRequestServiceInterface
{
    public function __construct(private readonly LeaveRequestRepositoryInterface $leaveRequestRepository)
    {
        parent::__construct($leaveRequestRepository);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\LeaveRequest>
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->leaveRequestRepository->getByEmployee($employeeId);
    }

    /**
     * @return array<int, \Modules\HR\Domain\Entities\LeaveRequest>
     */
    public function getPendingByEmployee(int $employeeId): array
    {
        return $this->leaveRequestRepository->getPendingByEmployee($employeeId);
    }

    protected function handle(array $data): mixed
    {
        throw new \BadMethodCallException(static::class.' does not support write operations via execute().');
    }
}
