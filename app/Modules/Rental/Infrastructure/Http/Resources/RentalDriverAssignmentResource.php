<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Rental\Domain\Entities\RentalDriverAssignment;

class RentalDriverAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var RentalDriverAssignment $assignment */
        $assignment = $this->resource;

        return [
            'id' => $assignment->getId(),
            'tenant_id' => $assignment->getTenantId(),
            'org_unit_id' => $assignment->getOrgUnitId(),
            'rental_booking_id' => $assignment->getRentalBookingId(),
            'employee_id' => $assignment->getEmployeeId(),
            'substitute_for_assignment_id' => $assignment->getSubstituteForAssignmentId(),
            'assignment_status' => $assignment->getAssignmentStatus(),
            'assigned_from' => $assignment->getAssignedFrom(),
            'assigned_to' => $assignment->getAssignedTo(),
            'substitution_reason' => $assignment->getSubstitutionReason(),
            'assigned_by' => $assignment->getAssignedBy(),
            'is_active' => $assignment->isActive(),
            'metadata' => $assignment->getMetadata(),
            'row_version' => $assignment->getRowVersion(),
            'created_at' => $assignment->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $assignment->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
