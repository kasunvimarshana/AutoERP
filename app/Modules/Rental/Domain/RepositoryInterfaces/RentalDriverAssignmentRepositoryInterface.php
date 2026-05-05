<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalDriverAssignment;

interface RentalDriverAssignmentRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?RentalDriverAssignment;

    /** @return RentalDriverAssignment[] */
    public function findByBooking(int $tenantId, int $bookingId, ?string $status = null): array;

    /** @return RentalDriverAssignment[] */
    public function findActiveByEmployee(int $tenantId, int $employeeId): array;

    public function save(RentalDriverAssignment $assignment): RentalDriverAssignment;

    public function delete(int $tenantId, int $id): bool;
}
