<?php

declare(strict_types=1);

namespace Modules\UOM\Services\UnitOfMeasures;

use Modules\Core\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Services\UomValidationService;
use Throwable;

final class UpdateUnitOfMeasureService
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
        private readonly UomValidationService $validation,
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
                return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'UnitOfMeasure not found.'));
            }

            $expectedVersion = isset($payload['expected_version'])
                ? (int) $payload['expected_version']
                : (isset($payload['row_version']) ? (int) $payload['row_version'] : null);

            $currentVersion = (int) $current->get('row_version', 1);
            if ($expectedVersion !== null && $expectedVersion !== $currentVersion) {
                return Result::failure(new Error(
                    UomErrorCode::CONFLICT,
                    'Unit of measure was changed by another transaction. Reload before updating.',
                ));
            }

            $payload['row_version'] = $currentVersion + 1;
            unset($payload['expected_version']);

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] ??= $current->get('organization_unit_id') ?? $this->currentOrganizationUnit->currentOrganizationUnitId();
            if (! $this->validation->organizationBelongsToTenant(
                isset($payload['organization_unit_id']) ? (int) $payload['organization_unit_id'] : null,
                $tenantId,
            )) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Organization unit does not belong to the active tenant.'));
            }
            if (array_key_exists('type', $payload)) {
                $payload['type'] = strtolower((string) $payload['type']);
            }
            if (array_key_exists('category', $payload)) {
                $payload['category'] = strtolower((string) $payload['category']);
            }
            if (array_key_exists('code', $payload)) {
                $payload['code'] = strtoupper(trim((string) $payload['code']));
            }

            $code = (string) ($payload['code'] ?? $current->get('code'));
            $name = (string) ($payload['name'] ?? $current->get('name'));

            foreach ($this->repository->list(['tenant_id' => $tenantId, 'code' => $code]) as $record) {
                if ((int) $record->get('id') !== (int) $id) {
                    return Result::failure(new Error(UomErrorCode::DUPLICATE_NAME, 'Unit code already exists.'));
                }
            }

            foreach ($this->repository->list(['tenant_id' => $tenantId, 'name' => $name]) as $record) {
                if ((int) $record->get('id') !== (int) $id) {
                    return Result::failure(new Error(
                        UomErrorCode::DUPLICATE_NAME,
                        'Unit of measure name already exists.',
                    ));
                }
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
