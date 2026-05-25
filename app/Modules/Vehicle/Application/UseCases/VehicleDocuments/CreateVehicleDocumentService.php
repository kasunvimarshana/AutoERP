<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\VehicleDocuments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\VehicleDocuments\CreateVehicleDocumentServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleDocumentRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class CreateVehicleDocumentService implements CreateVehicleDocumentServiceInterface
{
    public function __construct(private readonly VehicleDocumentRepositoryInterface $documents)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->documents->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
