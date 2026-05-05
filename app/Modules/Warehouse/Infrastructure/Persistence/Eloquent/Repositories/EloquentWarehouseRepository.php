<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class EloquentWarehouseRepository extends EloquentRepository implements WarehouseRepositoryInterface
{
    private readonly WarehouseModel $warehouseModel;

    public function __construct(WarehouseModel $model)
    {
        $this->warehouseModel = $model;
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (WarehouseModel $model): Warehouse => $this->mapModelToEntity($model));
    }

    public function save(Warehouse $warehouse): Warehouse
    {
        $data = [
            'tenant_id' => $warehouse->getTenantId(),
            'org_unit_id' => $warehouse->getOrgUnitId(),
            'name' => $warehouse->getName(),
            'code' => $warehouse->getCode(),
            'image_path' => $warehouse->getImagePath(),
            'type' => $warehouse->getType(),
            'address_id' => $warehouse->getAddressId(),
            'is_active' => $warehouse->isActive(),
            'is_default' => $warehouse->isDefault(),
            'metadata' => $warehouse->getMetadata(),
            'row_version' => $warehouse->getRowVersion(),
        ];

        if ($warehouse->getId() !== null) {
            $model = $this->update($warehouse->getId(), $data);
        } else {
            $model = $this->create($data);
        }

        /** @var WarehouseModel $model */

        return $this->toDomainEntity($model);
    }

    public function findByTenantAndCode(int $tenantId, string $code): ?Warehouse
    {
        /** @var WarehouseModel|null $model */
        $model = $this->warehouseModel->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('code', $code)
            ->first();

        return $model !== null ? $this->toDomainEntity($model) : null;
    }

    public function clearDefaultForTenant(int $tenantId, ?int $excludeWarehouseId = null): void
    {
        $query = $this->warehouseModel->newQuery()->where('tenant_id', $tenantId)->where('is_default', true);

        if ($excludeWarehouseId !== null) {
            $query->where('id', '!=', $excludeWarehouseId);
        }

        $query->update(['is_default' => false]);
    }

    private function mapModelToEntity(WarehouseModel $model): Warehouse
    {
        return new Warehouse(
            tenantId: (int) $model->tenant_id,
            orgUnitId: $model->org_unit_id !== null ? (int) $model->org_unit_id : null,
            name: (string) $model->name,
            code: $model->code,
            imagePath: $model->image_path,
            type: (string) $model->type,
            addressId: $model->address_id !== null ? (int) $model->address_id : null,
            isActive: (bool) $model->is_active,
            isDefault: (bool) $model->is_default,
            metadata: is_array($model->metadata) ? $model->metadata : null,
            rowVersion: (int) ($model->row_version ?? 1),
            id: (int) $model->id,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at,
        );
    }
}
