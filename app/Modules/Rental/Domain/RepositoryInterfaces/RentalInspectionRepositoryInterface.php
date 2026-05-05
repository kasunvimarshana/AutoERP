<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalInspection;

interface RentalInspectionRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?RentalInspection;

    /** @return RentalInspection[] */
    public function findByBooking(int $tenantId, int $bookingId): array;

    public function save(RentalInspection $inspection): RentalInspection;

    public function delete(int $tenantId, int $id): bool;
}
