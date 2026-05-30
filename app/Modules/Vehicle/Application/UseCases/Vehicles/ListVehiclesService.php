<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\Vehicles;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\ListVehiclesServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleDefaults;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class ListVehiclesService implements ListVehiclesServiceInterface
{
    public function __construct(
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly CurrentOrganizationUnitContextAccessorInterface $organizationUnitContext,
    ) {}

    public function execute(array $filters): Result
    {
        try {
            $tenantId = $this->resolveTenantId($filters);
            if ($tenantId instanceof Result) {
                return $tenantId;
            }

            $organizationUnitId = $this->resolveOrganizationUnitId($filters);
            if ($organizationUnitId instanceof Result) {
                return $organizationUnitId;
            }

            $result = $this->vehicles->pageByFilters(
                $tenantId,
                $organizationUnitId,
                isset($filters['vehicle_code']) ? trim((string) $filters['vehicle_code']) : null,
                isset($filters['vin']) ? trim((string) $filters['vin']) : null,
                isset($filters['license_plate']) ? trim((string) $filters['license_plate']) : null,
                isset($filters['status']) ? trim((string) $filters['status']) : null,
                max(
                    1,
                    (int) (
                        $filters['per_page']
                        ?? (int) config('vehicle.pagination.default_per_page', VehicleDefaults::DEFAULT_PER_PAGE)
                    ),
                ),
                max(1, (int) ($filters['page'] ?? VehicleDefaults::DEFAULT_PAGE)),
            );

            return Result::success($result);
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function resolveTenantId(array $filters): int|Result
    {
        $requestedTenantId = isset($filters['tenant_id']) ? (int) $filters['tenant_id'] : null;
        $currentTenantId = $this->tenantContext->currentTenantId();

        if ($currentTenantId !== null && $requestedTenantId !== null && $requestedTenantId !== $currentTenantId) {
            return $this->invalid('Tenant scope mismatch.', [
                'requested_tenant_id' => $requestedTenantId,
                'current_tenant_id' => $currentTenantId,
            ]);
        }

        $tenantId = $currentTenantId ?? $requestedTenantId;
        if ($tenantId === null || $tenantId < 1) {
            return $this->invalid('Tenant context is required to list vehicles.');
        }

        return $tenantId;
    }

    private function resolveOrganizationUnitId(array $filters): int|null|Result
    {
        $requestedOrganizationUnitId = isset($filters['organization_unit_id'])
            ? (int) $filters['organization_unit_id']
            : null;
        $currentOrganizationUnitId = $this->organizationUnitContext->currentOrganizationUnitId();

        if (
            $currentOrganizationUnitId !== null
            && $requestedOrganizationUnitId !== null
            && $requestedOrganizationUnitId !== $currentOrganizationUnitId
        ) {
            return $this->invalid('Organization unit scope mismatch.', [
                'requested_organization_unit_id' => $requestedOrganizationUnitId,
                'current_organization_unit_id' => $currentOrganizationUnitId,
            ]);
        }

        return $currentOrganizationUnitId ?? $requestedOrganizationUnitId;
    }

    /**
     * @param  array<string, scalar|array|null>  $context
     */
    private function invalid(string $message, array $context = []): Result
    {
        return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $message, $context));
    }
}
