<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Contracts;

use Modules\Rental\Domain\Entities\RentalBooking;

interface FindRentalBookingServiceInterface
{
    public function findById(int $tenantId, int $id): RentalBooking;

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;
}
