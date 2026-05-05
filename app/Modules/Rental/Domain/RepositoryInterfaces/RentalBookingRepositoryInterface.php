<?php

declare(strict_types=1);

namespace Modules\Rental\Domain\RepositoryInterfaces;

use Modules\Rental\Domain\Entities\RentalBooking;

interface RentalBookingRepositoryInterface
{
    public function save(RentalBooking $booking): RentalBooking;

    public function findById(int $tenantId, int $id): ?RentalBooking;

    public function findByBookingNumber(int $tenantId, string $bookingNumber): ?RentalBooking;

    /** @return array{data: RentalBooking[], total: int, per_page: int, current_page: int} */
    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array;

    public function existsByBookingNumber(int $tenantId, string $bookingNumber): bool;
}
