<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\AllocateLeaveBalanceServiceInterface;
use Modules\HR\Application\DTOs\LeaveBalanceData;
use Modules\HR\Domain\Entities\LeaveBalance;
use Modules\HR\Domain\RepositoryInterfaces\LeaveBalanceRepositoryInterface;

class AllocateLeaveBalanceService extends BaseService implements AllocateLeaveBalanceServiceInterface
{
    public function __construct(
        private readonly LeaveBalanceRepositoryInterface $balanceRepository,
    ) {
        parent::__construct($this->balanceRepository);
    }

    protected function handle(array $data): LeaveBalance
    {
        $dto = LeaveBalanceData::fromArray($data);
        $existing = $this->balanceRepository->findByEmployeeAndType(
            $dto->tenantId,
            $dto->employeeId,
            $dto->leaveTypeId,
            $dto->year,
        );

        $now = new \DateTimeImmutable;
        $balance = new LeaveBalance(
            tenantId: $dto->tenantId,
            employeeId: $dto->employeeId,
            leaveTypeId: $dto->leaveTypeId,
            year: $dto->year,
            allocated: $dto->allocated,
            used: $existing?->getUsed() ?? 0.0,
            pending: $existing?->getPending() ?? 0.0,
            carried: $dto->carried > 0.0 ? $dto->carried : ($existing?->getCarried() ?? 0.0),
            createdAt: $existing?->getCreatedAt() ?? $now,
            updatedAt: $now,
            id: $existing?->getId(),
        );

        return $this->balanceRepository->save($balance);
    }
}
