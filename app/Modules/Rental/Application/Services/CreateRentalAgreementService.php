<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Rental\Application\Contracts\CreateRentalAgreementServiceInterface;
use Modules\Rental\Domain\Entities\RentalAgreement;
use Modules\Rental\Domain\RepositoryInterfaces\RentalAgreementRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalReservationRepositoryInterface;

class CreateRentalAgreementService implements CreateRentalAgreementServiceInterface
{
    public function __construct(
        private readonly RentalAgreementRepositoryInterface $agreements,
        private readonly RentalReservationRepositoryInterface $reservations,
    ) {}

    public function execute(
        string $tenantId,
        string $reservationId,
        ?string $digitalAgreementUrl,
        string $securityDeposit,
        string $currencyCode,
        string $fuelPolicy,
        string $mileagePolicy,
    ): RentalAgreement {
        $reservation = $this->reservations->findById($reservationId);

        if ($reservation === null || $reservation->getTenantId() !== $tenantId) {
            throw new \RuntimeException('Reservation not found.');
        }

        $agreement = new RentalAgreement(
            id: (string) Str::uuid(),
            tenantId: $tenantId,
            reservationId: $reservationId,
            agreementNumber: 'AGR-' . strtoupper(Str::random(10)),
            digitalAgreementUrl: $digitalAgreementUrl,
            securityDeposit: $securityDeposit,
            currencyCode: $currencyCode,
            fuelPolicy: $fuelPolicy,
            mileagePolicy: $mileagePolicy,
            status: 'draft',
            signedAt: new \DateTime(),
        );

        DB::transaction(function () use ($agreement, $reservation): void {
            $reservation->confirm();
            $this->reservations->update($reservation);
            $this->agreements->create($agreement);
        });

        return $agreement;
    }
}
