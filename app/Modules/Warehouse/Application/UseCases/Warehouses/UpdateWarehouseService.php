<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\UseCases\Warehouses;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\UpdateWarehouseServiceInterface;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Constants\WarehouseErrorCode;
use Throwable;

final class UpdateWarehouseService implements UpdateWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository)
    {
    }

    public function execute(int|string $id, array $payload): Result
    {
        try {
            if ($this->repository->findById($id) === null) {
                return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}