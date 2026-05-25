<?php

declare(strict_types=1);

namespace Modules\Extension\Application\UseCases\Comments;

use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Extension\Application\Contracts\UseCases\Comments\ListCommentsServiceInterface;
use Modules\Extension\Application\Repositories\CommentRepositoryInterface;
use Modules\Extension\Domain\Constants\ExtensionDefaults;
use Modules\Extension\Domain\Constants\ExtensionErrorCode;
use Throwable;

final class ListCommentsService implements ListCommentsServiceInterface
{
    public function __construct(private readonly CommentRepositoryInterface $repository)
    {
    }

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
            return Result::failure(new Error(ExtensionErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}