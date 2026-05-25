<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UnitOfMeasures;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\ListUnitOfMeasuresServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Domain\Constants\UomDefaults;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class ListUnitOfMeasuresService implements ListUnitOfMeasuresServiceInterface
{
    public function __construct(private readonly UnitOfMeasureRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : UomDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('uom.pagination.max_per_page', UomDefaults::MAX_PER_PAGE))
                : (int) config('uom.pagination.default_per_page', UomDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}