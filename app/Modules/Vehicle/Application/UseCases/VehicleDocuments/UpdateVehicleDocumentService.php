<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\VehicleDocuments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\UpdateVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class UpdateVehicleDocumentService implements UpdateVehicleDocumentServiceInterface
{
    public function __construct(private readonly VehicleDocumentRepositoryInterface $documents)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->documents->findById($id) === null) {
                return Result::failure(new Error(VehicleErrorCode::NOT_FOUND, 'Vehicle document not found.'));
            }

            return Result::success($this->documents->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
