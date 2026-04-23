<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Core\Domain\Exceptions\DomainException;
use Modules\HR\Application\Contracts\CreateLeaveTypeServiceInterface;
use Modules\HR\Application\DTOs\LeaveTypeData;
use Modules\HR\Domain\Entities\LeaveType;
use Modules\HR\Domain\RepositoryInterfaces\LeaveTypeRepositoryInterface;

class CreateLeaveTypeService extends BaseService implements CreateLeaveTypeServiceInterface
{
    public function __construct(
        private readonly LeaveTypeRepositoryInterface $leaveTypeRepository,
    ) {
        parent::__construct($this->leaveTypeRepository);
    }

    protected function handle(array $data): LeaveType
    {
        $dto = LeaveTypeData::fromArray($data);

        $existing = $this->leaveTypeRepository->findByTenantAndCode($dto->tenantId, $dto->code);
        if ($existing !== null) {
            throw new DomainException("Leave type code '{$dto->code}' already exists for this tenant.");
        }

        $now = new \DateTimeImmutable;
        $leaveType = new LeaveType(
            tenantId: $dto->tenantId,
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
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->leaveTypeRepository->save($leaveType);
    }
}
