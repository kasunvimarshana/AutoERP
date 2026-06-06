<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\UseCases\WarehouseLocations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Warehouse\Application\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\Constants\WarehouseErrorCode;
use Throwable;

final class GetWarehouseLocationService
{
    public function __construct(private readonly WarehouseLocationRepositoryInterface $repository) {}

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'WarehouseLocation not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
