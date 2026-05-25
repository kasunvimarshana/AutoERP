<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\UseCases\VehicleRentalLessorAgreementDebitNotes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLessorAgreementDebitNotes\DeleteVehicleRentalLessorAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLessorAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class DeleteVehicleRentalLessorAgreementDebitNoteService implements DeleteVehicleRentalLessorAgreementDebitNoteServiceInterface
{
    public function __construct(private readonly VehicleRentalLessorAgreementDebitNoteRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'VehicleRentalLessorAgreementDebitNote not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}