<?php
namespace Modules\Inventory\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Inventory\Domain\Entities\InventoryBatch;
use Modules\Inventory\Domain\RepositoryInterfaces\InventoryBatchRepositoryInterface;
use Modules\Inventory\Infrastructure\Persistence\Eloquent\Models\InventoryBatchModel;

class EloquentInventoryBatchRepository extends EloquentRepository implements InventoryBatchRepositoryInterface
{
    public function __construct(InventoryBatchModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?InventoryBatch
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByNumber(int $tenantId, int $productId, string $batchNumber): ?InventoryBatch
    {
        $model = $this->model->where('tenant_id', $tenantId)
            ->where('product_id', $productId)
            ->where('batch_number', $batchNumber)
            ->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findByProduct(int $productId, int $tenantId): array
    {
        return $this->model->where('product_id', $productId)
            ->where('tenant_id', $tenantId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): InventoryBatch
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(InventoryBatch $batch, array $data): InventoryBatch
    {
        $model = $this->model->findOrFail($batch->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): InventoryBatch
    {
        return new InventoryBatch(
            id: $model->id,
            tenantId: $model->tenant_id,
            productId: $model->product_id,
            batchNumber: $model->batch_number,
            manufacturingDate: $model->manufacturing_date ? new \DateTimeImmutable($model->manufacturing_date) : null,
            expiryDate: $model->expiry_date ? new \DateTimeImmutable($model->expiry_date) : null,
            supplierId: $model->supplier_id,
            status: $model->status,
            attributes: $model->attributes,
        );
    }
}
