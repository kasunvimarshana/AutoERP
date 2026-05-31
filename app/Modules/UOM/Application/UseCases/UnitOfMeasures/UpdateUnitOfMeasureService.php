<?php

declare(strict_types=1);

namespace Modules\UOM\Application\UseCases\UnitOfMeasures;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\UOM\Application\Contracts\UseCases\UnitOfMeasures\UpdateUnitOfMeasureServiceInterface;
use Modules\UOM\Application\Repositories\UnitOfMeasureRepositoryInterface;
use Modules\UOM\Domain\Constants\UomErrorCode;
use Throwable;

final class UpdateUnitOfMeasureService implements UpdateUnitOfMeasureServiceInterface
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
        private readonly CurrentOrganizationUnitContextAccessorInterface $currentOrganizationUnit,
    ) {
    }

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

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] ??= $current->get('organization_unit_id') ?? $this->currentOrganizationUnit->currentOrganizationUnitId();
            $payload['category'] ??= $payload['type'] ?? $current->get('category') ?? $current->get('type');
            $payload['type'] ??= $payload['category'];
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
