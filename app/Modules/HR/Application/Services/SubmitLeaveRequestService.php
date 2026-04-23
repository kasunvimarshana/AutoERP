<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\SubmitLeaveRequestServiceInterface;
use Modules\HR\Application\DTOs\LeaveRequestData;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Domain\Entities\LeaveRequest;
use Modules\HR\Domain\Events\LeaveRequestSubmitted;
use Modules\HR\Domain\Exceptions\InsufficientLeaveBalanceException;
use Modules\HR\Domain\Exceptions\LeaveRequestConflictException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveBalanceRepositoryInterface;
use Modules\HR\Domain\RepositoryInterfaces\LeaveRequestRepositoryInterface;
use Modules\HR\Domain\ValueObjects\LeaveRequestStatus;

class SubmitLeaveRequestService extends BaseService implements SubmitLeaveRequestServiceInterface
{
    public function __construct(
        private readonly LeaveRequestRepositoryInterface $requestRepository,
        private readonly LeaveBalanceRepositoryInterface $balanceRepository,
    ) {
        parent::__construct($this->requestRepository);
    }

    protected function handle(array $data): LeaveRequest
    {
        $dto = LeaveRequestData::fromArray($data);

        $overlapping = $this->requestRepository->findOverlapping(
            $dto->tenantId,
            $dto->employeeId,
            $dto->startDate,
            $dto->endDate,
        );

        if ($overlapping !== []) {
            throw new LeaveRequestConflictException;
        }

        $year = (int) (new \DateTimeImmutable($dto->startDate))->format('Y');
        $balance = $this->balanceRepository->findByEmployeeAndType(
            $dto->tenantId,
            $dto->employeeId,
            $dto->leaveTypeId,
            $year,
        );

        if ($balance === null || ! $balance->canRequest($dto->totalDays)) {
            throw new InsufficientLeaveBalanceException;
        }

        $now = new \DateTimeImmutable;
        $request = new LeaveRequest(
            tenantId: $dto->tenantId,
            employeeId: $dto->employeeId,
            leaveTypeId: $dto->leaveTypeId,
            startDate: new \DateTimeImmutable($dto->startDate),
            endDate: new \DateTimeImmutable($dto->endDate),
            totalDays: $dto->totalDays,
            reason: $dto->reason,
            status: LeaveRequestStatus::PENDING,
            approverId: null,
            approverNote: '',
            attachmentPath: $dto->attachmentPath,
            metadata: $dto->metadata,
            createdAt: $now,
            updatedAt: $now,
        );

        $saved = $this->requestRepository->save($request);

        $updatedBalance = $this->withPending($balance, $balance->getPending() + $dto->totalDays);
        $this->balanceRepository->save($updatedBalance);

        $this->addEvent(new LeaveRequestSubmitted($saved, $dto->tenantId));

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
            pending: $pending,
            carried: $balance->getCarried(),
            createdAt: $balance->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $balance->getId(),
        );
    }
}
