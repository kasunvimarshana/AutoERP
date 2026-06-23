<?php

declare(strict_types=1);

namespace Modules\Extension\Services\EntityAttributes;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Extension\Constants\ExtensionDefaults;
use Modules\Extension\Constants\ExtensionErrorCode;
use Modules\Extension\Repositories\EntityAttributeRepositoryInterface;
use Throwable;

final class ListEntityAttributesService
{
    public function __construct(private readonly EntityAttributeRepositoryInterface $repository) {}

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : ExtensionDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('extension.pagination.max_per_page', ExtensionDefaults::MAX_PER_PAGE))
                : (int) config('extension.pagination.default_per_page', ExtensionDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(
                ExtensionErrorCode::INVALID_VALUE,
                'Unable to list entity attributes for the active tenant.',
            ));
        }
    }
}
