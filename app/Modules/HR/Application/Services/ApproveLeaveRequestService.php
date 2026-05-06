<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\ApproveLeaveRequestServiceInterface;
use Modules\HR\Application\DTOs\ApproveLeaveRequestData;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestApproved;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveBalanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\ValueObjects\LeaveRequestStatus;

class ApproveLeaveRequestService extends BaseService implements ApproveLeaveRequestServiceInterface
{
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $requestRepository,
        private readonly LeaveBalanceRepositoryInterface $balanceRepository,
    ) {
        parent::__construct($this->requestRepository);
    }

    protected function handle(array $data): LeaveRequest
    {
        $dto = ApproveLeaveRequestData::fromArray($data);
        $request = $this->requestRepository->find($dto->leaveRequestId);

        if ($request === null || $request->getTenantId() !== $dto->tenantId) {
            throw new LeaveRequestNotFoundException($dto->leaveRequestId);
        }

        if ($request->getStatus() !== LeaveRequestStatus::PENDING) {
            throw new DomainException('Only pending leave requests can be approved.');
        }

        $request->approve($dto->approverId, $dto->approverNote);
        $saved = $this->requestRepository->save($request);

        $year = (int) $request->getStartDate()->format('Y');
        $balance = $this->balanceRepository->findByEmployeeAndType(
            $dto->tenantId,
            $request->getEmployeeId(),
            $request->getLeaveTypeId(),
            $year,
        );

        if ($balance !== null) {
            $updatedBalance = $this->withUsedAndPending(
                $balance,
                $balance->getUsed() + $request->getTotalDays(),
                $balance->getPending() - $request->getTotalDays(),
            );
            $this->balanceRepository->save($updatedBalance);
        }

        $this->addEvent(new LeaveRequestApproved($saved, $dto->tenantId));

        return $saved;
    }

    private function withUsedAndPending(LeaveBalance $balance, float $used, float $pending): LeaveBalance
    {
        return new LeaveBalance(
            tenantId: $balance->getTenantId(),
            employeeId: $balance->getEmployeeId(),
            leaveTypeId: $balance->getLeaveTypeId(),
            year: $balance->getYear(),
            allocated: $balance->getAllocated(),
            used: max(0.0, $used),
            pending: max(0.0, $pending),
            carried: $balance->getCarried(),
            createdAt: $balance->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $balance->getId(),
        );
    }
}
