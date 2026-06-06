<?php

declare(strict_types=1);

namespace Modules\UOM\Services;

use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\DTOs\PagedResult;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\UOM\Constants\UomDefaults;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Throwable;

final class UomLookupService
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $uoms,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function activeLookup(array $criteria, int $perPage, int $page): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $criteria['tenant_id'] = $tenantId;
            $criteria['is_active'] = true;

            return Result::success($this->uoms->page(
                $criteria,
                $perPage > 0 ? min($perPage, UomDefaults::MAX_PER_PAGE) : 20,
                $page > 0 ? $page : 1,
            ));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    public function baseLookup(array $criteria, int $perPage, int $page): Result
    {
        $criteria['is_base'] = true;

        return $this->activeLookup($criteria, $perPage, $page);
    }
}
