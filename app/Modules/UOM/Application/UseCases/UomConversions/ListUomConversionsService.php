<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UomConversions;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Domain\Constants\UomDefaults;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class ListUomConversionsService
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function execute(array $criteria, int $perPage, int $page): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $criteria['tenant_id'] = $tenantId;
            $resolvedPage = $page > 0 ? $page : UomDefaults::DEFAULT_PAGE;
            $resolvedPerPage = $perPage > 0
                ? min($perPage, (int) config('uom.pagination.max_per_page', UomDefaults::MAX_PER_PAGE))
                : (int) config('uom.pagination.default_per_page', UomDefaults::DEFAULT_PER_PAGE);

            return Result::success($this->repository->page($criteria, $resolvedPerPage, $resolvedPage));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
