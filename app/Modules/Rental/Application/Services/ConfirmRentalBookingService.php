<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Rental\Application\Contracts\ConfirmRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\Events\RentalBookingConfirmed;
use Modules\Rental\Domain\Exceptions\RentalBookingNotFoundException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;

class ConfirmRentalBookingService implements ConfirmRentalBookingServiceInterface
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

        $booking->confirm();

        $saved = DB::transaction(fn (): RentalBooking => $this->bookingRepository->save($booking));

        Event::dispatch(new RentalBookingConfirmed($saved));

        return $saved;
    }
}
