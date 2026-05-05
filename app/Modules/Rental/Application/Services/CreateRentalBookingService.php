<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Rental\Application\Contracts\CreateRentalBookingServiceInterface;
use Modules\Rental\Domain\Entities\RentalBooking;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;

class CreateRentalBookingService implements CreateRentalBookingServiceInterface
{
    public function __construct(
        private readonly RentalBookingRepositoryInterface $bookingRepository,
    ) {}

    public function execute(array $data): RentalBooking
    {
        $booking = new RentalBooking(
            tenantId: (int) $data['tenant_id'],
            customerId: (int) $data['customer_id'],
            bookingNumber: (string) $data['booking_number'],
            bookingType: (string) $data['booking_type'],
            fleetSource: (string) $data['fleet_source'],
            status: $data['status'] ?? 'draft',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : null,
            scheduledStartAt: isset($data['scheduled_start_at']) ? new \DateTimeImmutable($data['scheduled_start_at']) : null,
            scheduledEndAt: isset($data['scheduled_end_at']) ? new \DateTimeImmutable($data['scheduled_end_at']) : null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
        );

        return DB::transaction(fn (): RentalBooking => $this->bookingRepository->save($booking));
    }
}
