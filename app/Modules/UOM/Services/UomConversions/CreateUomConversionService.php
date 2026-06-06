<?php

declare(strict_types=1);

namespace Modules\UOM\Services\UomConversions;

use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Services\UomValidationService;
use Throwable;

final class CreateUomConversionService
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
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

            $fromUomId = (int) ($payload['from_uom_id'] ?? 0);
            $toUomId = (int) ($payload['to_uom_id'] ?? 0);
            $factor = (float) ($payload['conversion_factor'] ?? 0);

            if ($fromUomId === $toUomId) {
                return Result::failure(new Error(
                    UomErrorCode::SELF_REFERENCE_CONVERSION,
                    'From and to UOM cannot be the same.',
                ));
            }

            if ($factor <= 0) {
                return Result::failure(new Error(
                    UomErrorCode::INVALID_FACTOR,
                    'Conversion factor must be greater than zero.',
                ));
            }

            $fromUom = $this->uomRepository->findByIdInTenant($fromUomId, $tenantId);
            $toUom = $this->uomRepository->findByIdInTenant($toUomId, $tenantId);

            if ($fromUom === null || $toUom === null) {
                return Result::failure(new Error(
                    UomErrorCode::NOT_FOUND,
                    'One or both units of measure were not found.',
                ));
            }

            if ((string) $fromUom->get('type') !== (string) $toUom->get('type')) {
                return Result::failure(new Error(
                    UomErrorCode::INCOMPATIBLE_UOM_TYPE,
                    'Conversion types are incompatible.',
                ));
            }

            if ($this->repository->findConversionBetween($fromUomId, $toUomId, $tenantId) !== null) {
                return Result::failure(new Error(UomErrorCode::DUPLICATE_CONVERSION, 'Conversion already exists.'));
            }

            return Result::success($this->repository->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
