<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementDebitNotes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\GetVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class GetVehicleRentalLessorAgreementDebitNoteService implements GetVehicleRentalLessorAgreementDebitNoteServiceInterface
{
    public function __construct(private readonly VehicleRentalLessorAgreementDebitNoteRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'VehicleRentalLessorAgreementDebitNote not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}