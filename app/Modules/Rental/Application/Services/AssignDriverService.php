<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\AssignDriverServiceInterface;
use Modules\Rental\Domain\Entities\RentalDriverAssignment;
use Modules\Rental\Domain\Exceptions\RentalBookingException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDriverAssignmentRepositoryInterface;

class AssignDriverService extends BaseService implements AssignDriverServiceInterface
{
    public function __construct(
        private readonly RentalDriverAssignmentRepositoryInterface $assignmentRepository,
        private readonly RentalBookingRepositoryInterface $bookingRepository,
    ) {}

    protected function handle(array $data): RentalDriverAssignment
    {
        $tenantId = (int) $data['tenant_id'];
        $bookingId = (int) $data['rental_booking_id'];

        $booking = $this->bookingRepository->findById($tenantId, $bookingId);
        if ($booking === null) {
            throw RentalBookingException::notFound($bookingId);
        }

        $assignment = new RentalDriverAssignment(
            tenantId: $tenantId,
            rentalBookingId: $bookingId,
            employeeId: (int) $data['employee_id'],
            assignmentStatus: 'assigned',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : $booking->getOrgUnitId(),
            assignedFrom: $data['assigned_from'] ?? $booking->getPickupAt(),
            assignedTo: $data['assigned_to'] ?? $booking->getReturnDueAt(),
            assignedBy: isset($data['assigned_by']) ? (int) $data['assigned_by'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->assignmentRepository->save($assignment);
    }
}
