<?php

declare(strict_types=1);

namespace Modules\Warehouse\Services\WarehouseLocations;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Core\Results\Error;
use Modules\Core\Results\Result;
use Modules\Warehouse\Constants\WarehouseErrorCode;
use Modules\Warehouse\Models\WarehouseLocationModel;
use Modules\Warehouse\Models\WarehouseModel;
use Modules\Warehouse\Services\WarehouseDomainService;
use Throwable;

final class CreateWarehouseLocationService
{
    public function __construct(private readonly WarehouseDomainService $domain) {}

    public function execute(array $payload): Result
    {
        try {
            $requestTenantId = (int) $payload['tenant_id'];
            $requestOrganizationUnitId = $this->domain->nullableInt($payload['organization_unit_id'] ?? null);
            $warehouse = WarehouseModel::query()
                ->forTenant($requestTenantId, $requestOrganizationUnitId)
                ->find((int) $payload['warehouse_id']);
            if (! $warehouse instanceof WarehouseModel) {
                return Result::failure(new Error(WarehouseErrorCode::NOT_FOUND, 'Warehouse not found.'));
            }
            $this->domain->assertWarehouseManaged($warehouse, $requestTenantId, $requestOrganizationUnitId);

            $tenantId = (int) $warehouse->tenant_id;
            $organizationUnitId = $this->domain->nullableInt($warehouse->organization_unit_id);
            $name = trim((string) $payload['name']);
            $code = $this->domain->nullableString($payload['code'] ?? null);
            $isActive = array_key_exists('is_active', $payload) ? (bool) $payload['is_active'] : true;
            $isDefault = array_key_exists('is_default', $payload) ? (bool) $payload['is_default'] : false;
            $this->domain->assertDefaultIsActive($isDefault, $isActive, 'Default warehouse location');

            return Result::success(DB::transaction(function () use ($payload, $warehouse, $tenantId, $organizationUnitId, $name, $code, $isActive, $isDefault): WarehouseLocationModel {
                if ($isDefault) {
                    $warehouse = WarehouseModel::query()->whereKey($warehouse->getKey())->lockForUpdate()->firstOrFail();
                    WarehouseLocationModel::query()
                        ->where('warehouse_id', (int) $warehouse->getKey())
                        ->where('is_default', true)
                        ->update([
                            'is_default' => false,
                            'row_version' => DB::raw('row_version + 1'),
                            'updated_at' => now(),
                        ]);
                }

                $parent = $this->domain->resolveParent(
                    $warehouse,
                    $this->domain->nullableInt($payload['parent_id'] ?? null),
                );
                $this->domain->assertLocationUnique($tenantId, (int) $warehouse->getKey(), $name, $code);
                $hierarchy = $this->domain->hierarchyAttributes($parent, $name, $code);

                return WarehouseLocationModel::query()->create([
                    'tenant_id' => $tenantId,
                    'organization_unit_id' => $organizationUnitId,
                    'metadata' => $payload['metadata'] ?? null,
                    'warehouse_id' => (int) $warehouse->getKey(),
                    'parent_id' => $parent?->getKey(),
                    'name' => $name,
                    'code' => $code,
                    'path' => $hierarchy['path'],
                    'depth' => $hierarchy['depth'],
                    'type' => $payload['type'] ?? 'bin',
                    'is_active' => $isActive,
                    'is_pickable' => array_key_exists('is_pickable', $payload) ? (bool) $payload['is_pickable'] : true,
                    'is_receivable' => array_key_exists('is_receivable', $payload) ? (bool) $payload['is_receivable'] : true,
                    'is_default' => $isDefault,
                    'capacity' => $payload['capacity'] ?? null,
                    'row_version' => 1,
                ])->load(['warehouse', 'parent', 'organizationUnit']);
            }, 3));
        } catch (InvalidArgumentException $exception) {
            return Result::failure(new Error(WarehouseErrorCode::INVALID_HIERARCHY, $exception->getMessage()));
        } catch (Throwable $exception) {
            report($exception);

            return Result::failure(new Error(WarehouseErrorCode::INVALID_VALUE, 'Warehouse location could not be created.'));
        }
    }
}
