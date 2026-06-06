<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\UseCases\Warehouses;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Constants\WarehouseErrorCode;
use Throwable;

final class GetWarehouseService
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository) {}

    public function execute(int|string $id): Result
    {
        try {
            $record = $this->repository->findById($id);

            if ($record === null) {
                return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
