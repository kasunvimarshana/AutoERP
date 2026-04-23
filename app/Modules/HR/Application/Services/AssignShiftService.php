<?php

declare(strict_types=1);

namespace Modules\HR\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\HR\Application\Contracts\AssignShiftServiceInterface;
use Modules\HR\Application\DTOs\ShiftAssignmentData;
use Modules\HR\Domain\Entities\ShiftAssignment;
use Modules\HR\Domain\RepositoryInterfaces\ShiftAssignmentRepositoryInterface;

class AssignShiftService extends BaseService implements AssignShiftServiceInterface
{
    public function __construct(
        private readonly ShiftAssignmentRepositoryInterface $assignmentRepository,
    ) {
        parent::__construct($this->assignmentRepository);
    }

    protected function handle(array $data): ShiftAssignment
    {
        $dto = ShiftAssignmentData::fromArray($data);

        $now = new \DateTimeImmutable;
        $assignment = new ShiftAssignment(
            tenantId: $dto->tenantId,
            employeeId: $dto->employeeId,
            shiftId: $dto->shiftId,
            effectiveFrom: new \DateTimeImmutable($dto->effectiveFrom),
            effectiveTo: $dto->effectiveTo !== null ? new \DateTimeImmutable($dto->effectiveTo) : null,
            createdAt: $now,
            updatedAt: $now,
        );

        return $this->assignmentRepository->save($assignment);
    }
}
