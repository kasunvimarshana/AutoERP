<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\Vehicles;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\UpdateVehicleServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class UpdateVehicleService implements UpdateVehicleServiceInterface
{
    private const STATUSES = [
        'draft',
        'active',
        'inactive',
        'in_service',
        'in_rental',
        'under_maintenance',
        'unavailable',
        'sold',
        'archived',
    ];

    public function __construct(
        private readonly VehicleRepositoryInterface $vehicles,
        private readonly CurrentTenantContextAccessorInterface $tenantContext,
        private readonly CurrentOrganizationUnitContextAccessorInterface $organizationUnitContext,
    ) {}

    public function execute(int|string $id, array $payload): Result
    {
        try {
            $tenantId = $this->tenantContext->currentTenantId();
            if ($tenantId === null || $tenantId < 1) {
                return $this->invalid('Tenant context is required to update a vehicle.');
            }

            if (
                isset($payload['tenant_id'])
                && (int) $payload['tenant_id'] !== $tenantId
            ) {
                return $this->invalid('Tenant scope mismatch.', [
                    'requested_tenant_id' => (int) $payload['tenant_id'],
                    'current_tenant_id' => $tenantId,
                ]);
            }

            $currentOrganizationUnitId = $this->organizationUnitContext->currentOrganizationUnitId();
            if (
                $currentOrganizationUnitId !== null
                && isset($payload['organization_unit_id'])
                && (int) $payload['organization_unit_id'] !== $currentOrganizationUnitId
            ) {
                return $this->invalid('Organization unit scope mismatch.', [
                    'requested_organization_unit_id' => (int) $payload['organization_unit_id'],
                    'current_organization_unit_id' => $currentOrganizationUnitId,
                ]);
            }

            if (isset($payload['status']) && ! in_array((string) $payload['status'], self::STATUSES, true)) {
                return $this->invalid('Vehicle status is not supported.', ['status' => (string) $payload['status']]);
            }

            if (array_key_exists('current_odometer', $payload) && (int) $payload['current_odometer'] < 0) {
                return $this->invalid('Current odometer cannot be negative.');
            }

            unset($payload['tenant_id']);

            if ($this->vehicles->findInTenant($tenantId, $id) === null) {
                return Result::failure(new Error(VehicleErrorCode::NOT_FOUND, 'Vehicle not found.'));
            }

            return Result::success($this->vehicles->updateInTenant($tenantId, $id, $payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    /**
     * @param  array<string, scalar|array|null>  $context
     */
    private function invalid(string $message, array $context = []): Result
    {
        return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $message, $context));
    }
}
