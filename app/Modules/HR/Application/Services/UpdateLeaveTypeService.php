<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\UpdateLeaveTypeServiceInterface;
use Modules\HR\Application\DTOs\LeaveTypeData;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\Exceptions\LeaveTypeNotFoundException;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;

class UpdateLeaveTypeService extends BaseService implements UpdateLeaveTypeServiceInterface
{
    public function __construct(
        private readonly LeaveTypeRepositoryInterface $leaveTypeRepository,
    ) {
        parent::__construct($this->leaveTypeRepository);
    }

    protected function handle(array $data): LeaveType
    {
        $id = (int) ($data['id'] ?? 0);
        $leaveType = $this->leaveTypeRepository->find($id);

        if ($leaveType === null) {
            throw new LeaveTypeNotFoundException($id);
        }

        $dto = LeaveTypeData::fromArray($data);

        if ($leaveType->getTenantId() !== $dto->tenantId) {
            throw new LeaveTypeNotFoundException($id);
        }

        $updated = new LeaveType(
            tenantId: $leaveType->getTenantId(),
            name: $dto->name,
            code: $dto->code,
            description: $dto->description,
            maxDaysPerYear: $dto->maxDaysPerYear,
            carryForwardDays: $dto->carryForwardDays,
            isPaid: $dto->isPaid,
            requiresApproval: $dto->requiresApproval,
            applicableGender: $dto->applicableGender,
            minServiceDays: $dto->minServiceDays,
            isActive: $dto->isActive,
            metadata: $dto->metadata,
            createdAt: $leaveType->getCreatedAt(),
            updatedAt: new \DateTimeImmutable,
            id: $leaveType->getId(),
        );

        return $this->leaveTypeRepository->save($updated);
    }
}
