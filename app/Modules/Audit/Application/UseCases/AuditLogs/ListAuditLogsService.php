<?php

declare(strict_types=1);

namespace Modules\Audit\Application\UseCases\AuditLogs;

use Modules\Audit\Application\Contracts\UseCases\AuditLogs\ListAuditLogsServiceInterface;
use Modules\Audit\Application\Repositories\AuditLogRepositoryInterface;
use Modules\Audit\Domain\Constants\AuditDefaults;
use Modules\Audit\Domain\Constants\AuditErrorCode;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Throwable;

final class ListAuditLogsService implements ListAuditLogsServiceInterface
{
    public function __construct(private readonly AuditLogRepositoryInterface $repository)
    {
    }

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $resolvedPage = $page > 0 ? $page : AuditDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('audit.pagination.max_per_page', AuditDefaults::MAX_PER_PAGE))
                : (int) config('audit.pagination.default_per_page', AuditDefaults::DEFAULT_PER_PAGE);

            unset($criteria['search']);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(AuditErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}