<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalBooking;

interface RentalBookingRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?RentalBooking;

    public function findByTenant(int $tenantId, int $orgUnitId = null, array $filters = []): array;

    public function save(RentalBooking $booking): RentalBooking;

    public function delete(int $tenantId, int $id): bool;

    /** @return RentalBooking[] */
    public function findConflictingBookings(
        int $tenantId,
        int $assetId,
        string $pickupAt,
        string $returnDueAt,
        ?int $excludeBookingId = null,
    ): array;

    public function nextBookingNumber(int $tenantId, ?int $orgUnitId): string;
}
