<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UnitOfMeasures;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class CreateUnitOfMeasureService
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
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
            $payload['row_version'] ??= 1;
            $payload['category'] ??= $payload['type'] ?? 'UNIT';
            $payload['type'] ??= $payload['category'];
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
