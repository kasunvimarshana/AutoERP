<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\WarehouseLocations;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Repositories\WarehouseLocationRepositoryInterface;
use Throwable;

final class DeleteWarehouseLocationService
{
    public function __construct(private readonly WarehouseLocationRepositoryInterface $repository) {}

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'WarehouseLocation not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
