<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\Warehouses;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Tenant\Repositories\TenantRepositoryInterface;
use Modules\Tenant\Services\TenantEntitlementService;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Services\WarehouseDomainService;
use Throwable;

final class CreateWarehouseService
{
    public function __construct(
        private readonly WarehouseDomainService $domain,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly TenantRepositoryInterface $tenants,
        private readonly TenantEntitlementService $entitlements,
    ) {}

    public function execute(array $payload): Result
    {
        try {
            $tenantId = (int) $payload['tenant_id'];
            $organizationUnitId = $this->domain->nullableInt($payload['organization_unit_id'] ?? null);
            $name = trim((string) $payload['name']);
            $code = $this->domain->nullableString($payload['code'] ?? null);
            $isActive = array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true;
            $isDefault = array_key_exists('is_default', $payload) ? (bool) $payload['is_default'] : false;
            $this->domain->assertDefaultIsActive($isDefault, $isActive, 'Default warehouse');

            return DB::transaction(function () use ($payload, $tenantId, $organizationUnitId, $name, $code, $isActive, $isDefault): Result {
                if ($this->tenants->lockById($tenantId) === null) {
                    return Result::failure(new Error(WarehouseErrorCode::SCOPE_MISMATCH, 'Tenant not found.'));
                }
                $warehouseLimit = $this->entitlements->limit($tenantId, 'max_warehouses');
                if ($warehouseLimit !== null && $this->warehouses->countByTenant($tenantId) >= $warehouseLimit) {
                    return Result::failure(new Error(
                        WarehouseErrorCode::PLAN_LIMIT_REACHED,
                        'The tenant plan warehouse limit has been reached.',
                    ));
                }

                if ($isDefault) {
                    $this->domain->lockScopeOwner($tenantId, $organizationUnitId);
                    WarehouseModel::query()
                        ->inExactScope($tenantId, $organizationUnitId)
                        ->where('is_default', true)
                        ->update([
                            'is_default' => false,
                            'row_version' => DB::raw('row_version + 1'),
                            'updated_at' => now(),
                        ]);
                }

                $this->domain->assertWarehouseUnique($tenantId, $organizationUnitId, $name, $code);

                $warehouse = WarehouseModel::query()->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => $payload['metadata'] ?? null,
                    'name' => $name,
                    'code' => $code,
                    'type' => $payload['type'] ?? 'standard',
                    'is_active' => $isActive,
                    'is_default' => $isDefault,
                    'row_version' => 1,
                ])->load(['organizationUnit', 'defaultLocation'])->loadCount('locations');

                return Result::success($warehouse);
            }, 3);
        } catch (InvalidArgumentException $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, $exception->getMessage()));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse could not be created.'));
        }
    }
}
