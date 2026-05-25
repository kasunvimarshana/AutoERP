<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\VehicleDocuments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\GetVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class GetVehicleDocumentService implements GetVehicleDocumentServiceInterface
{
    public function __construct(private readonly VehicleDocumentRepositoryInterface $documents)
    {
    }

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->documents->findById($id);

            if ($record === null) {
                return Result::failure(new Error(VehicleErrorCode::NOT_FOUND, 'Vehicle document not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
