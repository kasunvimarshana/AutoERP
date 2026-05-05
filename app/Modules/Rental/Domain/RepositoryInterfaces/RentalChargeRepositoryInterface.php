<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalCharge;

interface RentalChargeRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?RentalCharge;

    /** @return RentalCharge[] */
    public function findByBooking(int $tenantId, int $bookingId): array;

    public function save(RentalCharge $charge): RentalCharge;

    public function delete(int $tenantId, int $id): bool;
}
