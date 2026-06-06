<?php

declare(strict_types=1);

namespace Modules\UOM\Services\UnitOfMeasures;

use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\UOM\Constants\UomCategory;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Constants\UomType;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Services\UomValidationService;
use Throwable;

final class CreateUnitOfMeasureService
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly UomValidationService $validation,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] ??= $this->currentOrganizationUnit->currentOrganizationUnitId();
            if (! $this->validation->organizationBelongsToTenant(
                isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
                $tenantId,
            )) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Organization unit does not belong to the active tenant.'));
            }
            $payload['row_version'] ??= 1;
            $payload['type'] = strtolower((string) ($payload['type'] ?? UomType::UNIT));
            $payload['category'] = strtolower((string) ($payload['category'] ?? UomCategory::QUANTITY));
            $payload['code'] = strtoupper(trim((string) ($payload['code'] ?? $payload['symbol'] ?? '')));

            if ($payload['code'] === '') {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Unit code is required.'));
            }

            if ($this->repository->exists(['tenant_id' => $tenantId, 'code' => $payload['code']])) {
                return Result::failure(new Error(UomErrorCode::DUPLICATE_NAME, 'Unit code already exists.'));
            }

            if ($this->repository->exists(['tenant_id' => $tenantId, 'name' => (string) $payload['name']])) {
                return Result::failure(new Error(UomErrorCode::DUPLICATE_NAME, 'Unit of measure name already exists.'));
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
