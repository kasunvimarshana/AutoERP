<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventorySerial;
use Modules\Inventory\Domain\RepositoryInterfaces\InventorySerialRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventorySerialModel;

class EloquentInventorySerialRepository extends EloquentRepository implements InventorySerialRepositoryInterface
{
    public function __construct(InventorySerialModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?InventorySerial
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findBySerial(int $tenantId, string $serialNumber): ?InventorySerial
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('serial_number', $serialNumber)
            ->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function create(array $data): InventorySerial
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(InventorySerial $serial, array $data): InventorySerial
    {
        $model = $this->model->findOrFail($serial->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): InventorySerial
    {
        return new InventorySerial(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            serialNumber: $model->serial_number,
            status: $model->status,
            currentWarehouseId: $model->current_warehouse_id,
            currentLocationId: $model->current_location_id,
            batchId: $model->batch_id,
            warrantyExpiresAt: $model->warranty_expires_at ? new \DateTimeImmutable($model->warranty_expires_at) : null,
        );
    }
}
