<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementCreditNotes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementCreditNotes\GetVehicleRentalLessorAgreementCreditNoteServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class GetVehicleRentalLessorAgreementCreditNoteService implements GetVehicleRentalLessorAgreementCreditNoteServiceInterface
{
    public function __construct(private readonly VehicleRentalLessorAgreementCreditNoteRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'VehicleRentalLessorAgreementCreditNote not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}