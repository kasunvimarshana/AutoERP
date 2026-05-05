<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalCharge;

interface RentalChargeRepositoryInterface
{
    public function findById(int $id, int $tenantId): ?RentalCharge;

    /** @return RentalCharge[] */
    public function findByRental(int $rentalId, int $tenantId): array;

    public function save(RentalCharge $charge): RentalCharge;

    public function delete(int $id, int $tenantId): void;
}
