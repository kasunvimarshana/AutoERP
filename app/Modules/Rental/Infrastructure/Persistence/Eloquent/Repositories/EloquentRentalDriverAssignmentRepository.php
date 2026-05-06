<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Rental\Domain\Entities\RentalDriverAssignment;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDriverAssignmentRepositoryInterface;
use Modules\Rental\Infrastructure\Persistence\Eloquent\Models\RentalDriverAssignmentModel;

class EloquentRentalDriverAssignmentRepository implements RentalDriverAssignmentRepositoryInterface
{
    public function __construct(private readonly RentalDriverAssignmentModel $model) {}

    public function findById(int $tenantId, int $id): ?RentalDriverAssignment
    {
        /** @var RentalDriverAssignmentModel|null $model */
        $model = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->first();

        return $model !== null ? $this->mapModelToEntity($model) : null;
    }

    public function findByBooking(int $tenantId, int $bookingId, ?string $status = null): array
    {
        $query = $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('rental_booking_id', $bookingId);

        if ($status !== null) {
            $query->where('assignment_status', $status);
        }

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn (RentalDriverAssignmentModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function findActiveByEmployee(int $tenantId, int $employeeId): array
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('assignment_status', 'assigned')
            ->orderByDesc('assigned_from')
            ->get()
            ->map(fn (RentalDriverAssignmentModel $m) => $this->mapModelToEntity($m))
            ->all();
    }

    public function save(RentalDriverAssignment $assignment): RentalDriverAssignment
    {
        $payload = [
            'tenant_id' => $assignment->getTenantId(),
            'org_unit_id' => $assignment->getOrgUnitId(),
            'row_version' => $assignment->getRowVersion(),
            'rental_booking_id' => $assignment->getRentalBookingId(),
            'employee_id' => $assignment->getEmployeeId(),
            'substitute_for_assignment_id' => $assignment->getSubstituteForAssignmentId(),
            'assignment_status' => $assignment->getAssignmentStatus(),
            'assigned_from' => $assignment->getAssignedFrom(),
            'assigned_to' => $assignment->getAssignedTo(),
            'substitution_reason' => $assignment->getSubstitutionReason(),
            'assigned_by' => $assignment->getAssignedBy(),
            'metadata' => $assignment->getMetadata(),
        ];

        $id = $assignment->getId();

        if ($id !== null) {
            $this->model->newQuery()
                ->where('tenant_id', $assignment->getTenantId())
                ->where('id', $id)
                ->update($payload);

            /** @var RentalDriverAssignmentModel $saved */
            $saved = $this->model->newQuery()
                ->where('tenant_id', $assignment->getTenantId())
                ->where('id', $id)
                ->firstOrFail();
        } else {
            /** @var RentalDriverAssignmentModel $saved */
            $saved = $this->model->newQuery()->create($payload);
        }

        return $this->mapModelToEntity($saved);
    }

    public function delete(int $tenantId, int $id): bool
    {
        return (bool) $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('id', $id)
            ->delete();
    }

    private function mapModelToEntity(RentalDriverAssignmentModel $model): RentalDriverAssignment
    {
        return new RentalDriverAssignment(
            tenantId: (int) $model->tenant_id,
            rentalBookingId: (int) $model->rental_booking_id,
            employeeId: (int) $model->employee_id,
            assignmentStatus: (string) $model->assignment_status,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            substituteForAssignmentId: $model->substitute_for_assignment_id !== null
                ? (int) $model->substitute_for_assignment_id
                : null,
            assignedFrom: $model->assigned_from !== null ? (string) $model->assigned_from : null,
            assignedTo: $model->assigned_to !== null ? (string) $model->assigned_to : null,
            substitutionReason: $model->substitution_reason,
            assignedBy: $model->assigned_by !== null ? (int) $model->assigned_by : null,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) $model->row_version,
            id: (int) $model->id,
        );
    }
}
