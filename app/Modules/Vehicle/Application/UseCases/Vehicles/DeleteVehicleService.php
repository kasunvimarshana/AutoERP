<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\Vehicles;

use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\DeleteVehicleServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class DeleteVehicleService implements DeleteVehicleServiceInterface
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
    ) {}

    public function execute(int|string $id): Result
    {
        try {
            $tenantId = $this->tenantContext->currentTenantId();
            if ($tenantId === null || $tenantId < 1) {
                return Result::failure(new Error(
                    VehicleErrorCode::INVALID_VALUE,
                    'Tenant context is required to delete a vehicle.',
                ));
            }

            if ($this->vehicles->findInTenant($tenantId, $id) === null) {
                return Result::failure(new Error(VehicleErrorCode::NOT_FOUND, 'Vehicle not found.'));
            }

            $this->vehicles->deleteInTenant($tenantId, $id);

            return Result::success(null);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }
}
