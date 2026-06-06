<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\Warehouses;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Repositories\WarehouseRepositoryInterface;
use Throwable;

final class DeleteWarehouseService
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository) {}

    public function execute(int|string $id): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
            }

            $this->repository->delete($id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
