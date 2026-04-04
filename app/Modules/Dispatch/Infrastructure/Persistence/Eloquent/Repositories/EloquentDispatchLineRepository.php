<?php

namespace Modules\Dispatch\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Dispatch\Domain\Entities\DispatchLine;
use Modules\Dispatch\Domain\RepositoryInterfaces\DispatchLineRepositoryInterface;
use Modules\Dispatch\Infrastructure\Persistence\Eloquent\Models\DispatchLineModel;

class EloquentDispatchLineRepository extends EloquentRepository implements DispatchLineRepositoryInterface
{
    public function __construct(DispatchLineModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?DispatchLine
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByDispatch(int $dispatchId): array
    {
        return $this->model->where('dispatch_id', $dispatchId)
            ->get()
            ->map(fn($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): DispatchLine
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(DispatchLine $line, array $data): DispatchLine
    {
        $model = $this->model->findOrFail($line->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): DispatchLine
    {
        return new DispatchLine(
            id: $model->id,
            dispatchId: $model->dispatch_id,
            salesOrderLineId: $model->sales_order_line_id,
            productId: $model->product_id,
            dispatchedQty: (float) $model->dispatched_qty,
            locationId: $model->location_id,
            variantId: $model->variant_id,
            batchId: $model->batch_id,
            serialNumber: $model->serial_number,
            lotNumber: $model->lot_number,
        );
    }
}
