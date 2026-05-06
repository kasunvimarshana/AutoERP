<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\RejectLeaveRequestServiceInterface;
use Modules\HR\Application\DTOs\RejectLeaveRequestData;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestRejected;
use Modules\HR\Domain\Exceptions\LeaveRequestNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveBalanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\ValueObjects\LeaveRequestStatus;

class RejectLeaveRequestService extends BaseService implements RejectLeaveRequestServiceInterface
{
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $requestRepository,
        private readonly LeaveBalanceRepositoryInterface $balanceRepository,
    ) {
        parent::__construct($this->requestRepository);
    }

    protected function handle(array $data): LeaveRequest
    {
        $dto = RejectLeaveRequestData::fromArray($data);
        $request = $this->requestRepository->find($dto->leaveRequestId);

        if ($request === null || $request->getTenantId() !== $dto->tenantId) {
            throw new LeaveRequestNotFoundException($dto->leaveRequestId);
        }

        if ($request->getStatus() !== LeaveRequestStatus::PENDING) {
            throw new DomainException('Only pending leave requests can be rejected.');
        }

        $request->reject($dto->approverId, $dto->reason);
        $saved = $this->requestRepository->save($request);

        $year = (int) $request->getStartDate()->format('Y');
        $balance = $this->balanceRepository->findByEmployeeAndType(
            $dto->tenantId,
            $request->getEmployeeId(),
            $request->getLeaveTypeId(),
            $year,
        );

        if ($balance !== null) {
            $updatedBalance = $this->withPending(
                $balance,
                $balance->getPending() - $request->getTotalDays(),
            );
            $this->balanceRepository->save($updatedBalance);
        }

        $this->addEvent(new LeaveRequestRejected($saved, $dto->tenantId));

        return $saved;
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
