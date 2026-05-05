<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\SubstituteDriverServiceInterface;
use Modules\Rental\Domain\Entities\RentalDriverAssignment;
use Modules\Rental\Domain\Exceptions\RentalBookingException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDriverAssignmentRepositoryInterface;

class SubstituteDriverService extends BaseService implements SubstituteDriverServiceInterface
{
    public function __construct(
        private readonly RentalDriverAssignmentRepositoryInterface $assignmentRepository,
    ) {}

    protected function handle(array $data): RentalDriverAssignment
    {
        $tenantId = (int) $data['tenant_id'];
        $originalAssignmentId = (int) $data['original_assignment_id'];

        $original = $this->assignmentRepository->findById($tenantId, $originalAssignmentId);
        if ($original === null || ! $original->isActive()) {
            throw new \RuntimeException("Original assignment #{$originalAssignmentId} not found or is not active.");
        }

        // Mark original as replaced
        $replaced = new RentalDriverAssignment(
            tenantId: $original->getTenantId(),
            rentalBookingId: $original->getRentalBookingId(),
            employeeId: $original->getEmployeeId(),
            assignmentStatus: 'replaced',
            orgUnitId: $original->getOrgUnitId(),
            substituteForAssignmentId: $original->getSubstituteForAssignmentId(),
            assignedFrom: $original->getAssignedFrom(),
            assignedTo: $data['replaced_at'] ?? now()->toISOString(),
            substitutionReason: $data['reason'] ?? null,
            assignedBy: $original->getAssignedBy(),
            metadata: $original->getMetadata(),
            rowVersion: $original->getRowVersion() + 1,
            id: $original->getId(),
        );
        $this->assignmentRepository->save($replaced);

        // Create substitute assignment
        $substitute = new RentalDriverAssignment(
            tenantId: $tenantId,
            rentalBookingId: $original->getRentalBookingId(),
            employeeId: (int) $data['employee_id'],
            assignmentStatus: 'assigned',
            orgUnitId: $original->getOrgUnitId(),
            substituteForAssignmentId: $originalAssignmentId,
            assignedFrom: $data['assigned_from'] ?? now()->toISOString(),
            assignedTo: $data['assigned_to'] ?? null,
            substitutionReason: $data['reason'] ?? null,
            assignedBy: isset($data['assigned_by']) ? (int) $data['assigned_by'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->assignmentRepository->save($substitute);
    }
}
