<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Modules\Core\Application\Services\BaseService;
use Modules\Rental\Application\Contracts\HoldRentalDepositServiceInterface;
use Modules\Rental\Domain\Entities\RentalDeposit;
use Modules\Rental\Domain\Exceptions\RentalBookingException;
use Modules\Rental\Domain\RepositoryInterfaces\RentalBookingRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalDepositRepositoryInterface;

class HoldRentalDepositService extends BaseService implements HoldRentalDepositServiceInterface
{
    public function __construct(
        private readonly RentalDepositRepositoryInterface $depositRepository,
        private readonly RentalBookingRepositoryInterface $bookingRepository,
    ) {}

    protected function handle(array $data): RentalDeposit
    {
        $tenantId = (int) $data['tenant_id'];
        $bookingId = (int) $data['rental_booking_id'];

        $booking = $this->bookingRepository->findById($tenantId, $bookingId);
        if ($booking === null) {
            throw RentalBookingException::notFound($bookingId);
        }

        $deposit = new RentalDeposit(
            tenantId: $tenantId,
            rentalBookingId: $bookingId,
            currencyId: isset($data['currency_id']) ? (int) $data['currency_id'] : $booking->getCurrencyId(),
            heldAmount: (float) $data['held_amount'],
            status: 'held',
            orgUnitId: isset($data['org_unit_id']) ? (int) $data['org_unit_id'] : $booking->getOrgUnitId(),
            releasedAmount: 0.0,
            forfeitedAmount: 0.0,
            heldAt: $data['held_at'] ?? now()->toISOString(),
            paymentId: isset($data['payment_id']) ? (int) $data['payment_id'] : null,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : null,
        );

        return $this->depositRepository->save($deposit);
    }
}
