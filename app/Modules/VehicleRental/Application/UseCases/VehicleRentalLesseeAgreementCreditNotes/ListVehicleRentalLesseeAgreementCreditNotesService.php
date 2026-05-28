<?php

declare(strict_types=1);

namespace Modules\VehicleRental\Application\UseCases\VehicleRentalLesseeAgreementCreditNotes;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\VehicleRental\Application\Contracts\UseCases\VehicleRentalLesseeAgreementCreditNotes\ListVehicleRentalLesseeAgreementCreditNotesServiceInterface;
use Modules\VehicleRental\Application\Repositories\VehicleRentalLesseeAgreementCreditNoteRepositoryInterface;
use Modules\VehicleRental\Domain\Constants\VehicleRentalDefaults;
use Modules\VehicleRental\Domain\Constants\VehicleRentalErrorCode;
use Throwable;

final class ListVehicleRentalLesseeAgreementCreditNotesService implements ListVehicleRentalLesseeAgreementCreditNotesServiceInterface
{
    public function __construct(private readonly VehicleRentalLesseeAgreementCreditNoteRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : VehicleRentalDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('vehicle_rental.pagination.max_per_page', VehicleRentalDefaults::MAX_PER_PAGE))
                : (int) config('vehicle_rental.pagination.default_per_page', VehicleRentalDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleRentalErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
