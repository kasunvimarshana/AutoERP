<?php

namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\SalesOrder\Domain\Entities\SalesOrderLine;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;

class EloquentSalesOrderLineRepository extends EloquentRepository implements SalesOrderLineRepositoryInterface
{
    public function __construct(SalesOrderLineModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?SalesOrderLine
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findBySalesOrder(int $salesOrderId): array
    {
        return $this->model->where('sales_order_id', $salesOrderId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): SalesOrderLine
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(SalesOrderLine $line, array $data): SalesOrderLine
    {
        $model = $this->model->findOrFail($line->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): SalesOrderLine
    {
        return new SalesOrderLine(
            id: $model->id,
            salesOrderId: $model->sales_order_id,
            productId: $model->product_id,
            orderedQty: (float) $model->ordered_qty,
            unitPrice: (float) $model->unit_price,
            lineTotal: (float) $model->line_total,
            variantId: $model->variant_id,
            discountAmount: $model->discount_amount !== null ? (float) $model->discount_amount : null,
            notes: $model->notes,
            fulfilledQty: (float) $model->fulfilled_qty,
        );
    }
}
