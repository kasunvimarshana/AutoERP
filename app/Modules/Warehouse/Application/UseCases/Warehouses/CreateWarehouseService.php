<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\UseCases\Warehouses;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Warehouse\Application\Contracts\UseCases\Warehouses\CreateWarehouseServiceInterface;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Constants\WarehouseErrorCode;
use Throwable;

final class CreateWarehouseService implements CreateWarehouseServiceInterface
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository)
    {
    }

    public function execute(array $payload): Result
    {
        try {
            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}