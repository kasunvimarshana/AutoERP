<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalIncident;

interface RentalIncidentRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?RentalIncident;

    /** @return RentalIncident[] */
    public function findByBooking(int $tenantId, int $bookingId): array;

    /** @return RentalIncident[] */
    public function findByTenant(int $tenantId, ?int $orgUnitId = null, array $filters = []): array;

    public function save(RentalIncident $incident): RentalIncident;

    public function delete(int $tenantId, int $id): bool;
}
