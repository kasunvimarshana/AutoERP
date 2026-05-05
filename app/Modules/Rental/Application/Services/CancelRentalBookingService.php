<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Rental\Application\Contracts\CancelRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\Exceptions\RentalBookingNotFoundException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;

class CancelRentalBookingService implements CancelRentalBookingServiceInterface
{
    public function __construct(
        private readonly RentalBookingRepositoryInterface $bookingRepository,
    ) {}

    public function execute(int $tenantId, int $id): RentalBooking
    {
        $booking = $this->bookingRepository->findById($tenantId, $id);
        if ($booking === null) {
            throw new RentalBookingNotFoundException($id);
        }

        $booking->cancel();

        return DB::transaction(fn (): RentalBooking => $this->bookingRepository->save($booking));
    }
}
