<?php

namespace Modules\Returns\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\Returns\Domain\Entities\StockReturn;
use Modules\Returns\Domain\RepositoryInterfaces\StockReturnRepositoryInterface;
use Modules\Returns\Infrastructure\Persistence\Eloquent\Models\StockReturnModel;

class EloquentStockReturnRepository extends EloquentRepository implements StockReturnRepositoryInterface
{
    public function __construct(StockReturnModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?StockReturn
    {
        $model = parent::findById($id);

        return $model ? $this->toEntity($model) : null;
    }

    public function findByReturnNumber(int $tenantId, string $returnNumber): ?StockReturn
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('return_number', $returnNumber)->first();

        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);

        return $query->paginate($perPage);
    }

    public function create(array $data): StockReturn
    {
        $model = parent::create($data);

        return $this->toEntity($model);
    }

    public function update(StockReturn $return, array $data): StockReturn
    {
        $model = $this->model->findOrFail($return->id);
        $updated = parent::update($model, $data);

        return $this->toEntity($updated);
    }

    public function save(StockReturn $return): StockReturn
    {
        $model = $this->model->findOrFail($return->id);
        $updated = parent::update($model, [
            'status'             => $return->status,
            'approved_by'        => $return->approvedBy,
            'approved_at'        => $return->approvedAt,
            'completed_by'       => $return->completedBy,
            'completed_at'       => $return->completedAt,
            'credit_memo_number' => $return->creditMemoNumber,
            'total_amount'       => $return->totalAmount,
            'restocking_fee'     => $return->restockingFee,
            'reason'             => $return->reason,
            'notes'              => $return->notes,
        ]);

        return $this->toEntity($updated);
    }

    private function toEntity(object $model): StockReturn
    {
        return new StockReturn(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            returnNumber: $model->return_number,
            returnType: $model->return_type,
            status: $model->status,
            originalOrderId: $model->original_order_id,
            originalOrderType: $model->original_order_type,
            customerId: $model->customer_id,
            supplierId: $model->supplier_id,
            reason: $model->reason,
            totalAmount: $model->total_amount !== null ? (float) $model->total_amount : null,
            restockingFee: $model->restocking_fee !== null ? (float) $model->restocking_fee : null,
            creditMemoNumber: $model->credit_memo_number,
            notes: $model->notes,
            approvedBy: $model->approved_by,
            approvedAt: $model->approved_at ? new \DateTimeImmutable((string) $model->approved_at) : null,
            completedBy: $model->completed_by,
            completedAt: $model->completed_at ? new \DateTimeImmutable((string) $model->completed_at) : null,
        );
    }
}
