<?php

declare(strict_types=1);

namespace Modules\UOM\Services\UnitOfMeasures;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\UOM\Constants\UomErrorCode;
use Modules\UOM\Repositories\UnitOfMeasureRepositoryInterface;
use Throwable;

final class GetUnitOfMeasureService
{
    public function __construct(
        private readonly UnitOfMeasureRepositoryInterface $repository,
        private readonly CurrentTenantContextAccessorInterface $currentTenant,
    ) {}

    public function execute(int|string $id): Result
    {
        try {
            $tenantId = $this->currentTenant->currentTenantId();
            if ($tenantId === null) {
                return Result::failure(new Error(UomErrorCode::INVALID_VALUE, 'Tenant context is required.'));
            }

            $record = $this->repository->findByIdInTenant($id, $tenantId);

            if ($record === null) {
                $ownerTenantId = DB::table('unit_of_measures')
                    ->where('id', $id)
                    ->whereNull('deleted_at')
                    ->value('tenant_id');

                if (is_numeric($ownerTenantId) && (int) $ownerTenantId !== $tenantId) {
                    return Result::failure(new Error(UomErrorCode::FORBIDDEN, 'UnitOfMeasure belongs to a different tenant.'));
                }

                return Result::failure(new Error(UomErrorCode::NOT_FOUND, 'UnitOfMeasure not found.'));
            }

            return Result::success($record);
        } catch (Throwable $exception) {
            return Result::failure(new Error(UomErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
