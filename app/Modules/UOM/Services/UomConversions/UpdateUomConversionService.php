<?php

declare(strict_types=1);

namespace Modules\UOM\Services\UomConversions;

use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Core\Services\DecimalMath;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Services\UomValidationService;
use Throwable;

final class UpdateUomConversionService
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly UomValidationService $validation,
        private readonly DecimalMath $math,
    ) {}

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $current = $this->repository->findByIdInTenant($id, $tenantId);

            if ($current === null) {
                return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'UomConversion not found.'));
            }

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] ??= $current->get('organization_unit_id') ?? $this->currentOrganizationUnit->currentOrganizationUnitId();
            if (! $this->validation->organizationBelongsToTenant(
                isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
                $tenantId,
            )) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Organization unit does not belong to the active tenant.'));
            }
            $fromUomId = (int) ($payload['from_uom_id'] ?? $current->get('from_uom_id'));
            $toUomId = (int) ($payload['to_uom_id'] ?? $current->get('to_uom_id'));
            $factor = $this->math->normalize((string) ($payload['conversion_factor'] ?? $current->get('conversion_factor')));
            $validated = $this->validation->validateConversion($fromUomId, $toUomId, $factor, $tenantId);
            if (! $validated['valid']) {
                return Result::failure(new Error((string) $validated['code'], (string) $validated['message']));
            }

            $organizationUnitId = isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null;
            foreach ([$validated['from'], $validated['to']] as $uom) {
                $uomOrganizationUnitId = $uom?->get('organization_unit_id');
                if ($organizationUnitId !== null && $uomOrganizationUnitId !== null && (int) $uomOrganizationUnitId !== $organizationUnitId) {
                    return Result::failure(new Error(
                        UomErrorCode::INVALID_VALUE,
                        'Conversion UOMs must belong to the active organization unit.',
                    ));
                }
            }

            $existing = $this->repository->findConversionBetween($fromUomId, $toUomId, $tenantId);
            if ($existing !== null && (int) $existing->get('id') !== (int) $id) {
                return Result::failure(new Error(UomErrorCode::DUPLICATE_CONVERSION, 'Conversion already exists.'));
            }

            $payload['conversion_factor'] = $factor;

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
