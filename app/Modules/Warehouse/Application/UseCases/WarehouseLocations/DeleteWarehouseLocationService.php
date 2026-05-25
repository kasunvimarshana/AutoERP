<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\UseCases\WarehouseLocations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Warehouse\Application\Contracts\UseCases\WarehouseLocations\DeleteWarehouseLocationServiceInterface;
use Modules\Warehouse\Application\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Domain\Constants\WarehouseErrorCode;
use Throwable;

final class DeleteWarehouseLocationService implements DeleteWarehouseLocationServiceInterface
{
    public function __construct(private readonly WarehouseLocationRepositoryInterface $repository)
    {
    }

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