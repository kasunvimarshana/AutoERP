<?php

declare(strict_types=1);

namespace Modules\Audit\Services\AuditLogs;

use Modules\Audit\Constants\AuditDefaults;
use Modules\Audit\Constants\AuditErrorCode;
use Modules\Audit\DTOs\AuditLogQueryData;
use Modules\Audit\Repositories\AuditLogRepositoryInterface;
use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Contracts\ErrorNormalizerInterface;
use Modules\Core\Results\Result;
use Throwable;

final class ListAuditLogsService
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly ErrorNormalizerInterface $errorNormalizer,
    ) {}

    public function execute(AuditLogQueryData $query): Result
    {
        try {
            $resolvedPage = $query->page > 0 ? $query->page : AuditDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $query->perPage > 0
                ? min($query->perPage, (int) config('audit.pagination.max_per_page', AuditDefaults::MAX_PER_PAGE))
                : (int) config('audit.pagination.default_per_page', AuditDefaults::DEFAULT_PER_PAGE);

            $tenantId = $this->currentTenant->currentTenantId();
            $organizationUnitId = $this->currentOrganizationUnit->currentOrganizationUnitId();

            $resolvedQuery = new AuditLogQueryData(
                $tenantId ?? $query->tenantId,
                $organizationUnitId ?? $query->organizationUnitId,
                $query->userId,
                $query->event,
                $query->auditableType,
                $query->auditableId,
                $query->fromDate,
                $query->toDate,
                $resolvedPerPage,
                $resolvedPage,
            );

            return Result::success($this->repository->pageByQuery($resolvedQuery));
        } catch (Throwable $exception) {
            return Result::failure($this->errorNormalizer->normalize(
                $exception,
                AuditErrorCode::INVALID_VALUE,
            ));
        }
    }
}
