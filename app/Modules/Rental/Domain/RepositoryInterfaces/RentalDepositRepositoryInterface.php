<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalDeposit;

interface RentalDepositRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?RentalDeposit;

    /** @return RentalDeposit[] */
    public function findByBooking(int $tenantId, int $bookingId): array;

    public function save(RentalDeposit $deposit): RentalDeposit;

    public function delete(int $tenantId, int $id): bool;
}
