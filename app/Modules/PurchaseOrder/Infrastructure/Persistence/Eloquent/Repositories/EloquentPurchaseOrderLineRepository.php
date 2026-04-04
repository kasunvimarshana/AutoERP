<?php
namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrderLine;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;

class EloquentPurchaseOrderLineRepository extends EloquentRepository implements PurchaseOrderLineRepositoryInterface
{
    public function __construct(PurchaseOrderLineModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?PurchaseOrderLine
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByPurchaseOrder(int $purchaseOrderId): array
    {
        return $this->model->where('purchase_order_id', $purchaseOrderId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): PurchaseOrderLine
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(PurchaseOrderLine $line, array $data): PurchaseOrderLine
    {
        $model = $this->model->findOrFail($line->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): PurchaseOrderLine
    {
        return new PurchaseOrderLine(
            id: $model->id,
            purchaseOrderId: $model->purchase_order_id,
            productId: $model->product_id,
            orderedQty: (float) $model->ordered_qty,
            unitCost: (float) $model->unit_cost,
            lineTotal: (float) $model->line_total,
            variantId: $model->variant_id,
            notes: $model->notes,
            receivedQty: (float) $model->received_qty,
        );
    }
}
