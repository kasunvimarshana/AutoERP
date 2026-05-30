<?php

declare(strict_types=1);

namespace Modules\Vehicle\Application\UseCases\Vehicles;

use Modules\Core\Application\Contracts\CurrentOrganizationUnitContextAccessorInterface;
use Modules\Core\Application\Contracts\CurrentTenantContextAccessorInterface;
use Modules\Core\Application\Results\Error;
use Modules\Core\Application\Results\Result;
use Modules\Vehicle\Application\Contracts\UseCases\Vehicles\CreateVehicleServiceInterface;
use Modules\Vehicle\Application\Repositories\VehicleRepositoryInterface;
use Modules\Vehicle\Domain\Constants\VehicleErrorCode;
use Throwable;

final class CreateVehicleService implements CreateVehicleServiceInterface
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

    public function execute(array $payload): Result
    {
        try {
            $tenantId = $this->resolveTenantId($payload);
            if ($tenantId instanceof Result) {
                return $tenantId;
            }

            $organizationUnitId = $this->resolveOrganizationUnitId($payload);
            if ($organizationUnitId instanceof Result) {
                return $organizationUnitId;
            }

            $status = (string) ($payload['status'] ?? 'active');
            if (! in_array($status, self::STATUSES, true)) {
                return $this->invalid('Vehicle status is not supported.', ['status' => $status]);
            }

            if (array_key_exists('current_odometer', $payload) && (int) $payload['current_odometer'] < 0) {
                return $this->invalid('Current odometer cannot be negative.');
            }

            $payload['tenant_id'] = $tenantId;
            $payload['organization_unit_id'] = $organizationUnitId;
            $payload['status'] = $status;

            if (! array_key_exists('row_version', $payload)) {
                $payload['row_version'] = 1;
            }

            return Result::success($this->vehicles->create($payload));
        } catch (Throwable $exception) {
            return Result::failure(new Error(VehicleErrorCode::INVALID_VALUE, $exception->getMessage()));
        }
    }

    private function resolveTenantId(array $payload): int|Result
    {
        $requestedTenantId = isset($payload['tenant_id']) ? (int) $payload['tenant_id'] : null;
        $currentTenantId = $this->tenantContext->currentTenantId();

        if ($currentTenantId !== null && $requestedTenantId !== null && $requestedTenantId !== $currentTenantId) {
            return $this->invalid('Tenant scope mismatch.', [
                'requested_tenant_id' => $requestedTenantId,
                'current_tenant_id' => $currentTenantId,
            ]);
        }

        $tenantId = $currentTenantId ?? $requestedTenantId;
        if ($tenantId === null || $tenantId < 1) {
            return $this->invalid('Tenant context is required to create a vehicle.');
        }

        return $tenantId;
    }

    private function resolveOrganizationUnitId(array $payload): int|null|Result
    {
        $requestedOrganizationUnitId = isset($payload['organization_unit_id'])
            ? (int) $payload['organization_unit_id']
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
