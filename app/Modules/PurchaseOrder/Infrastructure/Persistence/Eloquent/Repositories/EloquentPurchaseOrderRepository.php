<?php
namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrder;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderModel;

class EloquentPurchaseOrderRepository extends EloquentRepository implements PurchaseOrderRepositoryInterface
{
    public function __construct(PurchaseOrderModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?PurchaseOrder
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByPoNumber(int $tenantId, string $poNumber): ?PurchaseOrder
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('po_number', $poNumber)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): PurchaseOrder
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(PurchaseOrder $po, array $data): PurchaseOrder
    {
        $model = $this->model->findOrFail($po->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function save(PurchaseOrder $po): PurchaseOrder
    {
        $model = $this->model->findOrFail($po->id);
        $updated = parent::update($model, [
            'status'      => $po->status,
            'approved_by' => $po->approvedBy,
            'approved_at' => $po->approvedAt,
        ]);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): PurchaseOrder
    {
        return new PurchaseOrder(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            supplierId: $model->supplier_id,
            poNumber: $model->po_number,
            status: $model->status,
            totalAmount: $model->total_amount !== null ? (float) $model->total_amount : null,
            taxAmount: $model->tax_amount !== null ? (float) $model->tax_amount : null,
            currency: $model->currency,
            notes: $model->notes,
            expectedDeliveryDate: $model->expected_delivery_date ? new \DateTimeImmutable((string) $model->expected_delivery_date) : null,
            approvedAt: $model->approved_at ? new \DateTimeImmutable((string) $model->approved_at) : null,
            approvedBy: $model->approved_by,
        );
    }
}
