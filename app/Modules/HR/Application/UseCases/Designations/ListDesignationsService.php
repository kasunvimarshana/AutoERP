<?php

declare(strict_types=1);

namespace Modules\HR\Application\UseCases\Designations;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\HR\Application\Contracts\UseCases\Designations\ListDesignationsServiceInterface;
use Modules\HR\Application\Repositories\DesignationRepositoryInterface;
use Modules\HR\Domain\Constants\HrDefaults;
use Modules\HR\Domain\Constants\HrErrorCode;
use Throwable;

final class ListDesignationsService implements ListDesignationsServiceInterface
{
    public function __construct(private readonly DesignationRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : HrDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('hr.pagination.max_per_page', HrDefaults::MAX_PER_PAGE))
                : (int) config('hr.pagination.default_per_page', HrDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(HrErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}