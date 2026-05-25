<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\UseCases\PutAwayTasks;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Inventory\Application\Contracts\UseCases\PutAwayTasks\ListPutAwayTasksServiceInterface;
use Modules\Inventory\Application\Repositories\PutAwayTaskRepositoryInterface;
use Modules\Inventory\Domain\Constants\InventoryDefaults;
use Modules\Inventory\Domain\Constants\InventoryErrorCode;
use Throwable;

final class ListPutAwayTasksService implements ListPutAwayTasksServiceInterface
{
    public function __construct(private readonly PutAwayTaskRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : InventoryDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('inventory.pagination.max_per_page', InventoryDefaults::MAX_PER_PAGE))
                : (int) config('inventory.pagination.default_per_page', InventoryDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(InventoryErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}