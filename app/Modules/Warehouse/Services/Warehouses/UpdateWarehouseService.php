<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\Warehouses;

use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Repositories\WarehouseRepositoryInterface;
use Throwable;

final class UpdateWarehouseService
{
    public function __construct(private readonly WarehouseRepositoryInterface $repository) {}

    public function execute(int|string $id, int $tenantId, ?int $organizationUnitId, array $payload): Result
    {
        try {
            if (! $this->repository->exists([
                'id' => $id,
                'tenant_id' => $tenantId,
                'organization_unit_id' => $organizationUnitId,
            ])) {
                return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
            }

            return Result::success($this->repository->update($id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
