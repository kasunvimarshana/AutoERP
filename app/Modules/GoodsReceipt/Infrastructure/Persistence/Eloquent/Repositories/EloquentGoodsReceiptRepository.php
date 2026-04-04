<?php
namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel;

class EloquentGoodsReceiptRepository extends EloquentRepository implements GoodsReceiptRepositoryInterface
{
    public function __construct(GoodsReceiptModel $model)
    {
        parent::__construct($model);
    }

    public function findById(int $id): ?GoodsReceipt
    {
        $model = parent::findById($id);
        return $model ? $this->toEntity($model) : null;
    }

    public function findByGrNumber(int $tenantId, string $grNumber): ?GoodsReceipt
    {
        $model = $this->model->where('tenant_id', $tenantId)->where('gr_number', $grNumber)->first();
        return $model ? $this->toEntity($model) : null;
    }

    public function findAll(int $tenantId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where('tenant_id', $tenantId);
        $this->applyFilters($query, $filters);
        return $query->paginate($perPage);
    }

    public function create(array $data): GoodsReceipt
    {
        $model = parent::create($data);
        return $this->toEntity($model);
    }

    public function update(GoodsReceipt $gr, array $data): GoodsReceipt
    {
        $model = $this->model->findOrFail($gr->id);
        $updated = parent::update($model, $data);
        return $this->toEntity($updated);
    }

    public function save(GoodsReceipt $gr): GoodsReceipt
    {
        $model = $this->model->findOrFail($gr->id);
        $updated = parent::update($model, [
            'status'       => $gr->status,
            'inspected_by' => $gr->inspectedBy,
            'inspected_at' => $gr->inspectedAt,
            'put_away_by'  => $gr->putAwayBy,
            'put_away_at'  => $gr->putAwayAt,
            'received_by'  => $gr->receivedBy,
            'received_at'  => $gr->receivedAt,
        ]);
        return $this->toEntity($updated);
    }

    private function toEntity(object $model): GoodsReceipt
    {
        return new GoodsReceipt(
            id: $model->id,
            tenantId: $model->tenant_id,
            warehouseId: $model->warehouse_id,
            grNumber: $model->gr_number,
            status: $model->status,
            purchaseOrderId: $model->purchase_order_id,
            supplierId: $model->supplier_id,
            supplierReference: $model->supplier_reference,
            notes: $model->notes,
            receivedAt: $model->received_at ? new \DateTimeImmutable((string) $model->received_at) : null,
            receivedBy: $model->received_by,
            inspectedBy: $model->inspected_by,
            inspectedAt: $model->inspected_at ? new \DateTimeImmutable((string) $model->inspected_at) : null,
            putAwayBy: $model->put_away_by,
            putAwayAt: $model->put_away_at ? new \DateTimeImmutable((string) $model->put_away_at) : null,
        );
    }
}
