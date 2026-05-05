<?php

declare(strict_types=1);

namespace Modules\Rental\Application\Services;

use Illuminate\Support\Facades\DB;
use Modules\Rental\Application\Contracts\CheckInVehicleServiceInterface;
use Modules\Rental\Application\Contracts\CalculateRentalChargeServiceInterface;
use Modules\Rental\Application\DTOs\CheckInRentalDTO;
use Modules\Rental\Domain\Entities\RentalTransaction;
use Modules\Rental\Domain\RepositoryInterfaces\RentalAgreementRepositoryInterface;
use Modules\Rental\Domain\RepositoryInterfaces\RentalTransactionRepositoryInterface;
use Modules\ReturnRefund\Application\Contracts\ProcessReturnAndRefundServiceInterface;
use Modules\ReturnRefund\Application\DTOs\ProcessReturnInput;

class CheckInVehicleService implements CheckInVehicleServiceInterface
{
    public function __construct(
        private readonly RentalAgreementRepositoryInterface $agreements,
        private readonly RentalTransactionRepositoryInterface $transactions,
        private readonly CalculateRentalChargeServiceInterface $chargeCalculator,
        private readonly ProcessReturnAndRefundServiceInterface $processReturnAndRefund,
    ) {
    }

    public function execute(CheckInRentalDTO $dto): RentalTransaction
    {
        $agreement = $this->agreements->findById($dto->agreementId);

        if ($agreement === null || $agreement->getTenantId() !== $dto->tenantId) {
            throw new \RuntimeException('Agreement not found.');
        }

        $transaction = $this->transactions->findOpenByAgreementId($dto->agreementId);

        if ($transaction === null) {
            throw new \RuntimeException('Open rental transaction not found.');
        }

        $transaction->checkIn(
            checkedInAt: new \DateTime(),
            odometerIn: $dto->odometerIn,
            fuelLevelIn: $dto->fuelLevelIn,
            dropoffLatitude: $dto->dropoffLatitude,
            dropoffLongitude: $dto->dropoffLongitude,
        );

        $agreement->complete();
        $grossAmount = $this->chargeCalculator->execute($agreement, $transaction);

        DB::transaction(function () use ($agreement, $transaction, $grossAmount): void {
            $this->agreements->update($agreement);
            $this->transactions->update($transaction);

            $this->processReturnAndRefund->execute(new ProcessReturnInput(
                tenantId: $agreement->getTenantId(),
                rentalTransactionId: $transaction->getId(),
                grossAmount: $grossAmount,
                isDamaged: false,
                damageNotes: '',
                damageCharge: '0.000000',
                fuelAdjustmentCharge: '0.000000',
                lateReturnCharge: '0.000000',
            ));
        });

        return $transaction;
    }
}
