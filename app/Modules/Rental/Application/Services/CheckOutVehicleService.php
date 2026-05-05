<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Rental\Application\Contracts\CheckOutVehicleServiceInterface;
use Modules\Rental\Application\DTOs\CheckOutRentalDTO;
use Modules\Rental\Domain\Entities\RentalTransaction;
use Modules\Rental\Domain\RepositoryInterfaces\RentalAgreementRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalTransactionRepositoryInterface;

class CheckOutVehicleService implements CheckOutVehicleServiceInterface
{
    public function __construct(
        private readonly RentalAgreementRepositoryInterface $agreements,
        private readonly RentalTransactionRepositoryInterface $transactions,
    ) {}

    public function execute(CheckOutRentalDTO $dto): RentalTransaction
    {
        $agreement = $this->agreements->findById($dto->agreementId);

        if ($agreement === null || $agreement->getTenantId() !== $dto->tenantId) {
            throw new \RuntimeException('Agreement not found.');
        }

        $agreement->activate();

        $transaction = new RentalTransaction(
            id: (string) Str::uuid(),
            tenantId: $dto->tenantId,
            agreementId: $dto->agreementId,
            checkedOutAt: new \DateTime(),
            checkedInAt: null,
            odometerOut: $dto->odometerOut,
            odometerIn: null,
            fuelLevelOut: $dto->fuelLevelOut,
            fuelLevelIn: null,
            pickupLatitude: $dto->pickupLatitude,
            pickupLongitude: $dto->pickupLongitude,
            dropoffLatitude: null,
            dropoffLongitude: null,
            status: 'open',
        );

        DB::transaction(function () use ($agreement, $transaction): void {
            $this->agreements->update($agreement);
            $this->transactions->create($transaction);
        });

        return $transaction;
    }
}
