<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceipt;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptModel;

class EloquentGoodsReceiptRepository extends EloquentRepository implements GoodsReceiptRepositoryInterface
{
    public function __construct(GoodsReceiptModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (GoodsReceiptModel $m): GoodsReceipt => $this->mapModelToDomainEntity($m));
    }

    public function save(GoodsReceipt $goodsReceipt): GoodsReceipt
    {
        $savedModel = null;

        DB::transaction(function () use ($goodsReceipt, &$savedModel) {
            $data = [
                'tenant_id'         => $goodsReceipt->getTenantId(),
                'reference_number'  => $goodsReceipt->getReferenceNumber(),
                'status'            => $goodsReceipt->getStatus(),
                'purchase_order_id' => $goodsReceipt->getPurchaseOrderId(),
                'supplier_id'       => $goodsReceipt->getSupplierId(),
                'warehouse_id'      => $goodsReceipt->getWarehouseId(),
                'received_date'     => $goodsReceipt->getReceivedDate()?->format('Y-m-d'),
                'currency'          => $goodsReceipt->getCurrency(),
                'notes'             => $goodsReceipt->getNotes(),
                'metadata'          => $goodsReceipt->getMetadata()->toArray(),
                'received_by'       => $goodsReceipt->getReceivedBy(),
                'approved_by'       => $goodsReceipt->getApprovedBy(),
                'approved_at'       => $goodsReceipt->getApprovedAt()?->format('Y-m-d H:i:s'),
                'put_away_by'       => $goodsReceipt->getPutAwayBy(),
                'inspected_by'      => $goodsReceipt->getInspectedBy(),
                'inspected_at'      => $goodsReceipt->getInspectedAt()?->format('Y-m-d H:i:s'),
            ];

            if ($goodsReceipt->getId()) {
                $savedModel = $this->update($goodsReceipt->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof GoodsReceiptModel) {
            throw new \RuntimeException('Failed to save GoodsReceipt.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findById(int $id): ?GoodsReceipt
    {
        $model = $this->findModel($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByPurchaseOrder(int $tenantId, int $poId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('purchase_order_id', $poId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findBySupplier(int $tenantId, int $supplierId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('supplier_id', $supplierId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function findByStatus(int $tenantId, string $status): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('status', $status)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    public function list(array $filters = [], ?int $perPage = null, int $page = 1): mixed
    {
        $query = $this->model->newQuery();

        foreach ($filters as $column => $value) {
            $query->where($column, $value);
        }

        if ($perPage !== null) {
            return $query->paginate($perPage, ['*'], 'page', $page);
        }

        return $query->get()->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(GoodsReceiptModel $model): GoodsReceipt
    {
        return new GoodsReceipt(
            tenantId:        $model->tenant_id,
            referenceNumber: $model->reference_number,
            supplierId:      $model->supplier_id,
            purchaseOrderId: $model->purchase_order_id,
            warehouseId:     $model->warehouse_id,
            receivedDate:    $model->received_date,
            currency:        $model->currency,
            notes:           $model->notes,
            metadata:        isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            status:          $model->status,
            receivedBy:      $model->received_by,
            approvedBy:      $model->approved_by,
            approvedAt:      $model->approved_at,
            inspectedBy:     $model->inspected_by ?? null,
            inspectedAt:     $model->inspected_at ?? null,
            putAwayBy:       $model->put_away_by ?? null,
            id:              $model->id,
            createdAt:       $model->created_at,
            updatedAt:       $model->updated_at,
        );
    }
}
