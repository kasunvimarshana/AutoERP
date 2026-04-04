<?php

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\Entities\StockReturnLine;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnLineRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnLineModel;

class EloquentStockReturnLineRepository extends EloquentRepository implements StockReturnLineRepositoryInterface
{
    public function __construct(StockReturnLineModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?StockReturnLine
    {
        $model = parent::findById($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByStockReturn(int $stockReturnId): array
    {
        return $this->model->where('stock_return_id', $stockReturnId)
            ->get()
            ->map(fn ($m) => $this->toEntity($m))
            ->all();
    }

    public function create(array $data): StockReturnLine
    {
        $model = parent::create($data);

        return $this->toEntity($model);
    }

    public function update(StockReturnLine $line, array $data): StockReturnLine
    {
        $model = $this->model->findOrFail($line->id);
        $updated = parent::update($model, $data);

        return $this->toEntity($updated);
    }

    public function save(StockReturnLine $line): StockReturnLine
    {
        $model = $this->model->findOrFail($line->id);
        $updated = parent::update($model, [
            'quality_check_result' => $line->qualityCheckResult,
            'quality_checked_by'   => $line->qualityCheckedBy,
            'quality_checked_at'   => $line->qualityCheckedAt,
            'condition'            => $line->condition,
            'restock_action'       => $line->restockAction,
            'notes'                => $line->notes,
        ]);

        return $this->toEntity($updated);
    }

    private function toEntity(object $model): StockReturnLine
    {
        return new StockReturnLine(
            id: $model->id,
            stockReturnId: $model->stock_return_id,
            productId: $model->product_id,
            returnQty: (float) $model->return_qty,
            condition: $model->condition,
            qualityCheckResult: $model->quality_check_result,
            locationId: $model->location_id,
            variantId: $model->variant_id,
            originalBatchId: $model->original_batch_id,
            originalLotNumber: $model->original_lot_number,
            originalSerialNumber: $model->original_serial_number,
            unitPrice: $model->unit_price !== null ? (float) $model->unit_price : null,
            lineTotal: $model->line_total !== null ? (float) $model->line_total : null,
            restockAction: $model->restock_action,
            notes: $model->notes,
            qualityCheckedBy: $model->quality_checked_by,
            qualityCheckedAt: $model->quality_checked_at ? new \DateTimeImmutable((string) $model->quality_checked_at) : null,
        );
    }
}
