<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalSettlement;

interface RentalSettlementRepositoryInterface
{
    public function findById(int $tenantId, int $id): ?RentalSettlement;

    /** @return RentalSettlement[] */
    public function findByBooking(int $tenantId, int $bookingId): array;

    public function save(RentalSettlement $settlement): RentalSettlement;

    public function delete(int $tenantId, int $id): bool;
}
