<?php

declare(strict_types=1);

namespace Modules\Warehouse\Application\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Application\DTO\DataRecord;
use Modules\Core\Application\DTO\PagedResult;
use Modules\Tenant\Application\Repositories\TenantRepositoryInterface;
use Modules\Warehouse\Application\Actions\DeleteWarehouseRecordAction;
use Modules\Warehouse\Application\Actions\ListWarehouseRecordsAction;
use Modules\Warehouse\Application\Actions\PersistWarehouseRecordAction;
use Modules\Warehouse\Application\DTOs\WarehouseData;
use Modules\Warehouse\Application\DTOs\WarehouseLocationData;
use Modules\Warehouse\Application\Repositories\WarehouseLocationRepositoryInterface;
use Modules\Warehouse\Application\Repositories\WarehouseRepositoryInterface;
use Modules\Warehouse\Domain\Exceptions\WarehouseRecordNotFoundException;
use Modules\Warehouse\Domain\Services\WarehouseDomainService;

class WarehouseService
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenants,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly WarehouseDomainService $domain,
        private readonly ListWarehouseRecordsAction $listRecords,
        private readonly PersistWarehouseRecordAction $persistRecord,
        private readonly DeleteWarehouseRecordAction $deleteRecord,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listWarehouses(
        int|string $tenantId,
        array $filters = [],
        ?int $perPage = null,
    ): array|PagedResult {
        $this->findTenant($tenantId);

        return $this->listRecords->execute(
            $this->warehouses,
            array_merge(['tenant_id' => (int) $tenantId], $filters),
            $perPage,
        );
    }

    public function findWarehouse(int|string $tenantId, int|string $id): Model
    {
        $record = $this->warehouses->findForTenantById($tenantId, $id);

        if ($record === null) {
            throw WarehouseRecordNotFoundException::for('Warehouse', $id);
        }

        return $record;
    }

    public function createWarehouse(WarehouseData $data): DataRecord
    {
        $this->findTenant($data->tenantId);

        return $this->persistRecord->create($this->warehouses, $this->warehouseAttributes($data));
    }

    public function updateWarehouse(int|string $tenantId, int|string $id, WarehouseData $data): DataRecord
    {
        $this->findWarehouse($tenantId, $id);

        return $this->persistRecord->update(
            $this->warehouses,
            $id,
            $this->warehouseAttributes($data),
        );
    }

    public function deleteWarehouse(int|string $tenantId, int|string $id): bool
    {
        $this->findWarehouse($tenantId, $id);

        return $this->deleteRecord->execute($this->warehouses, $id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listLocations(
        int|string $tenantId,
        int|string $warehouseId,
        array $filters = [],
        ?int $perPage = null,
    ): array|PagedResult {
        $this->findWarehouse($tenantId, $warehouseId);

        return $this->listRecords->execute(
            $this->locations,
            array_merge(['tenant_id' => (int) $tenantId, 'warehouse_id' => (int) $warehouseId], $filters),
            $perPage,
        );
    }

    public function findLocation(int|string $tenantId, int|string $warehouseId, int|string $id): Model
    {
        $record = $this->locations->findForTenantById($tenantId, $id);

        if ($record === null || (int) $record->warehouse_id !== (int) $warehouseId) {
            throw WarehouseRecordNotFoundException::for('Warehouse location', $id);
        }

        return $record;
    }

    public function createLocation(WarehouseLocationData $data): DataRecord
    {
        $warehouse = $this->findWarehouse($data->tenantId, $data->warehouseId);

        if ($data->parentId !== null) {
            $this->findLocation($data->tenantId, $data->warehouseId, $data->parentId);
        }

        return $this->persistRecord->create($this->locations, $this->locationAttributes($data, $warehouse));
    }

    public function updateLocation(
        int|string $tenantId,
        int|string $warehouseId,
        int|string $id,
        WarehouseLocationData $data,
    ): DataRecord {
        $warehouse = $this->findWarehouse($tenantId, $warehouseId);

        if ($data->parentId !== null) {
            $this->findLocation($tenantId, $warehouseId, $data->parentId);
        }

        $this->findLocation($tenantId, $warehouseId, $id);

        return $this->persistRecord->update($this->locations, $id, $this->locationAttributes($data, $warehouse));
    }

    public function deleteLocation(int|string $tenantId, int|string $warehouseId, int|string $id): bool
    {
        $this->findLocation($tenantId, $warehouseId, $id);

        return $this->deleteRecord->execute($this->locations, $id);
    }

    private function findTenant(int|string $tenantId): Model
    {
        $method = 'findById';

        if (! method_exists($this->tenants, $method)) {
            throw WarehouseRecordNotFoundException::for('Tenant', $tenantId);
        }

        $tenant = $this->tenants->{$method}($tenantId);

        if ($tenant === null) {
            throw WarehouseRecordNotFoundException::for('Tenant', $tenantId);
        }

        return $tenant;
    }

    /**
     * @return array<string, mixed>
     */
    private function warehouseAttributes(WarehouseData $data): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId,
            'name' => $this->domain->normalizeRequiredText($data->name, 'Name'),
            'code' => $this->domain->normalizeCode($data->code),
            'image_path' => $this->domain->normalizeOptionalText($data->imagePath),
            'type' => $this->domain->normalizeWarehouseType($data->type),
            'is_active' => $data->isActive,
            'is_default' => $data->isDefault,
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function locationAttributes(WarehouseLocationData $data, Model $warehouse): array
    {
        return [
            'tenant_id' => $data->tenantId,
            'organization_unit_id' => $data->organizationUnitId ?? $warehouse->organization_unit_id,
            'warehouse_id' => $data->warehouseId,
            'parent_id' => $data->parentId,
            'name' => $this->domain->normalizeRequiredText($data->name, 'Name'),
            'code' => $this->domain->normalizeCode($data->code),
            'path' => $this->domain->normalizeOptionalText($data->path),
            'depth' => $this->domain->normalizeDepth($data->depth),
            'type' => $this->domain->normalizeLocationType($data->type),
            'is_active' => $data->isActive,
            'is_pickable' => $data->isPickable,
            'is_receivable' => $data->isReceivable,
            'capacity' => $this->domain->normalizeCapacity($data->capacity),
            'metadata' => $this->domain->normalizeMetadata($data->metadata),
        ];
    }
}
