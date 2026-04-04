<?php
namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceiptLine;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptLineModel;

class EloquentGoodsReceiptLineRepository extends EloquentRepository implements GoodsReceiptLineRepositoryInterface
{
    public function __construct(GoodsReceiptLineModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?GoodsReceiptLine
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByGoodsReceipt(int $goodsReceiptId): array
    {
        return $this->model->where('goods_receipt_id', $goodsReceiptId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): GoodsReceiptLine
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(GoodsReceiptLine $line, array $data): GoodsReceiptLine
    {
        $model = $this->model->findOrFail($line->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): GoodsReceiptLine
    {
        return new GoodsReceiptLine(
            id: $model->id,
            goodsReceiptId: $model->goods_receipt_id,
            productId: $model->product_id,
            locationId: $model->location_id,
            expectedQty: (float) $model->expected_qty,
            receivedQty: (float) $model->received_qty,
            variantId: $model->variant_id,
            purchaseOrderLineId: $model->purchase_order_line_id,
            batchId: $model->batch_id,
            lotNumber: $model->lot_number,
            serialNumber: $model->serial_number,
            unitCost: $model->unit_cost !== null ? (float) $model->unit_cost : null,
            condition: $model->condition ?? 'good',
            notes: $model->notes,
        );
    }
}
