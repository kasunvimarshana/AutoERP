<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementDebitNotes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementDebitNotes\UpdateVehicleRentalLesseeAgreementDebitNoteServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementDebitNoteRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class UpdateVehicleRentalLesseeAgreementDebitNoteService implements UpdateVehicleRentalLesseeAgreementDebitNoteServiceInterface
{
    public function __construct(private readonly VehicleRentalLesseeAgreementDebitNoteRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(VehicleRentalErrorCode::NOT_FOUND, 'VehicleRentalLesseeAgreementDebitNote not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}