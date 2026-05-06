<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\CancelLeaveRequestServiceInterface;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveBalanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\ValueObjects\LeaveRequestStatus;

class CancelLeaveRequestService extends BaseService implements CancelLeaveRequestServiceInterface
{
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $requestRepository,
        private readonly LeaveBalanceRepositoryInterface $balanceRepository,
    ) {
        parent::__construct($this->requestRepository);
    }

    protected function handle(array $data): LeaveRequest
    {
        $id = (int) ($data['leave_request_id'] ?? 0);
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $request = $this->requestRepository->find($id);

        if ($request === null || $request->getTenantId() !== $tenantId) {
            throw new LeaveRequestNotFoundException($id);
        }

        $previousStatus = $request->getStatus();

        if (! in_array($previousStatus, [LeaveRequestStatus::PENDING, LeaveRequestStatus::APPROVED], true)) {
            throw new DomainException('Only pending or approved leave requests can be cancelled.');
        }

        $request->cancel();
        $saved = $this->requestRepository->save($request);

        $year = (int) $request->getStartDate()->format('Y');
        $balance = $this->balanceRepository->findByEmployeeAndType(
            $tenantId,
            $request->getEmployeeId(),
            $request->getLeaveTypeId(),
            $year,
        );

        if ($balance !== null) {
            $updatedBalance = $previousStatus === LeaveRequestStatus::APPROVED
                ? $this->withUsed($balance, $balance->getUsed() - $request->getTotalDays())
                : $this->withPending($balance, $balance->getPending() - $request->getTotalDays());
            $this->balanceRepository->save($updatedBalance);
        }

        return $saved;
    }

    private function withUsed(LeaveBalance $balance, float $used): LeaveBalance
    {
        return new LeaveBalance(
            tenantId: $balance->getTenantId(),
            employeeId: $balance->getEmployeeId(),
            leaveTypeId: $balance->getLeaveTypeId(),
            year: $balance->getYear(),
            allocated: $balance->getAllocated(),
            used: max(0.0, $used),
            pending: $balance->getPending(),
            carried: $balance->getCarried(),
            createdAt: $balance->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $balance->getId(),
        );
    }

    private function withPending(LeaveBalance $balance, float $pending): LeaveBalance
    {
        return new LeaveBalance(
            tenantId: $balance->getTenantId(),
            employeeId: $balance->getEmployeeId(),
            leaveTypeId: $balance->getLeaveTypeId(),
            year: $balance->getYear(),
            allocated: $balance->getAllocated(),
            used: $balance->getUsed(),
            pending: max(0.0, $pending),
            carried: $balance->getCarried(),
            createdAt: $balance->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $balance->getId(),
        );
    }
}
