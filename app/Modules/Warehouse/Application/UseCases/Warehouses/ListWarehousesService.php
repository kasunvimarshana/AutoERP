<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\UseCases\Warehouses;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Constants\WarehouseDefaults;
use Modules\Warehouse\Domain\Constants\WarehouseErrorCode;
use Throwable;

final class ListWarehousesService
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository) {}

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : WarehouseDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('warehouse.pagination.max_per_page', WarehouseDefaults::MAX_PER_PAGE))
                : (int) config('warehouse.pagination.default_per_page', WarehouseDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
