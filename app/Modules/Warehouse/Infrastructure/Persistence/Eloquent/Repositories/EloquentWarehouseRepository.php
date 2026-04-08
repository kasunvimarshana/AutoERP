<?php

declare(strict_types=1);

namespace Modules\Warehouse\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Code;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Domain\ValueObjects\Name;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Warehouse\Domain\Entities\Warehouse;
use Modules\Warehouse\Domain\RepositoryInterfaces\WarehouseRepositoryInterface;
use Modules\Warehouse\Infrastructure\Persistence\Eloquent\Models\WarehouseModel;

class EloquentWarehouseRepository extends EloquentRepository implements WarehouseRepositoryInterface
{
    public function __construct(WarehouseModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (WarehouseModel $model): Warehouse => $this->mapModelToDomainEntity($model));
    }

    /**
     * {@inheritdoc}
     */
    public function save(Warehouse $warehouse): Warehouse
    {
        $savedModel = null;

        DB::transaction(function () use ($warehouse, &$savedModel) {
            if ($warehouse->getId()) {
                $data = [
                    'tenant_id'   => $warehouse->getTenantId(),
                    'name'        => $warehouse->getName()->value(),
                    'type'        => $warehouse->getType(),
                    'code'        => $warehouse->getCode()?->value(),
                    'description' => $warehouse->getDescription(),
                    'address'     => $warehouse->getAddress(),
                    'capacity'    => $warehouse->getCapacity(),
                    'location_id' => $warehouse->getLocationId(),
                    'metadata'    => $warehouse->getMetadata()?->toArray(),
                    'is_active'   => $warehouse->isActive(),
                ];
                $savedModel = $this->update($warehouse->getId(), $data);
            } else {
                $savedModel = $this->model->create([
                    'tenant_id'   => $warehouse->getTenantId(),
                    'name'        => $warehouse->getName()->value(),
                    'type'        => $warehouse->getType(),
                    'code'        => $warehouse->getCode()?->value(),
                    'description' => $warehouse->getDescription(),
                    'address'     => $warehouse->getAddress(),
                    'capacity'    => $warehouse->getCapacity(),
                    'location_id' => $warehouse->getLocationId(),
                    'metadata'    => $warehouse->getMetadata()?->toArray(),
                    'is_active'   => $warehouse->isActive(),
                ]);
            }
        });

        if (! $savedModel instanceof WarehouseModel) {
            throw new \RuntimeException('Failed to save warehouse.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    /**
     * {@inheritdoc}
     */
    public function getByLocation(int $locationId): array
    {
        return $this->model->where('location_id', $locationId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m))
            ->all();
    }

    private function mapModelToDomainEntity(WarehouseModel $model): Warehouse
    {
        return new Warehouse(
            tenantId:    $model->tenant_id,
            name:        new Name($model->name),
            type:        $model->type,
            code:        $model->code !== null ? new Code($model->code) : null,
            description: $model->description,
            address:     $model->address,
            capacity:    isset($model->capacity) ? (float) $model->capacity : null,
            locationId:  $model->location_id,
            metadata:    isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            isActive:    (bool) $model->is_active,
            id:          $model->id,
            createdAt:   $model->created_at,
            updatedAt:   $model->updated_at
        );
    }
}
