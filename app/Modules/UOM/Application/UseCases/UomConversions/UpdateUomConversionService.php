<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UomConversions;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Application\Repositories\UomConversionRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class UpdateUomConversionService
{
    public function __construct(
        private readonly UomConversionRepositoryInterface $repository,
        private readonly UnitOfMeasureRepositoryInterface $uomRepository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
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
            $fromUomId = (int) ($payload['from_uom_id'] ?? $current->get('from_uom_id'));
            $toUomId = (int) ($payload['to_uom_id'] ?? $current->get('to_uom_id'));
            $itemId = array_key_exists('item_id', $payload)
                ? ($payload['item_id'] === null ? null : (int) $payload['item_id'])
                : ($current->get('item_id') === null ? null : (int) $current->get('item_id'));
            $factor = (float) ($payload['factor'] ?? $current->get('factor'));

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

            $existing = $this->repository->findConversionBetween($fromUomId, $toUomId, $tenantId, $itemId);
            if ($existing !== null && (int) $existing->get('id') !== (int) $id) {
                return Result::failure(new Error(UomErrorCode::DUPLICATE_CONVERSION, 'Conversion already exists.'));
            }

            $payload['category'] ??= $fromUom->get('category') ?? $fromUom->get('type');

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
