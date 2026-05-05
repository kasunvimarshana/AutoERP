<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Rental\Application\Contracts\FindRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\Exceptions\RentalBookingNotFoundException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;

class FindRentalBookingService implements FindRentalBookingServiceInterface
{
    public function __construct(
        private readonly RentalBookingRepositoryInterface $bookingRepository,
    ) {}

    public function findById(int $tenantId, int $id): RentalBooking
    {
        $booking = $this->bookingRepository->findById($tenantId, $id);
        if ($booking === null) {
            throw new RentalBookingNotFoundException($id);
        }

        return $booking;
    }

    public function paginate(int $tenantId, array $filters, int $perPage, int $page): array
    {
        return $this->bookingRepository->paginate($tenantId, $filters, $perPage, $page);
    }
}
