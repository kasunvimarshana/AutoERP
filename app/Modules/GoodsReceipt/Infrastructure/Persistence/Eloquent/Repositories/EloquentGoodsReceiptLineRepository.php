<?php

declare(strict_types=1);

namespace Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\GoodsReceipt\Domain\Entities\GoodsReceiptLine;
use Modules\GoodsReceipt\Domain\RepositoryInterfaces\GoodsReceiptLineRepositoryInterface;
use Modules\GoodsReceipt\Infrastructure\Persistence\Eloquent\Models\GoodsReceiptLineModel;

class EloquentGoodsReceiptLineRepository extends EloquentRepository implements GoodsReceiptLineRepositoryInterface
{
    public function __construct(GoodsReceiptLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (GoodsReceiptLineModel $m): GoodsReceiptLine => $this->mapModelToDomainEntity($m));
    }

    public function save(GoodsReceiptLine $line): GoodsReceiptLine
    {
        $savedModel = null;

        DB::transaction(function () use ($line, &$savedModel) {
            $data = [
                'tenant_id'              => $line->getTenantId(),
                'goods_receipt_id'       => $line->getGoodsReceiptId(),
                'line_number'            => $line->getLineNumber(),
                'purchase_order_line_id' => $line->getPurchaseOrderLineId(),
                'product_id'             => $line->getProductId(),
                'variation_id'           => $line->getVariationId(),
                'batch_id'               => $line->getBatchId(),
                'serial_number'          => $line->getSerialNumber(),
                'uom_id'                 => $line->getUomId(),
                'quantity_expected'      => $line->getQuantityExpected(),
                'quantity_received'      => $line->getQuantityReceived(),
                'quantity_accepted'      => $line->getQuantityAccepted(),
                'quantity_rejected'      => $line->getQuantityRejected(),
                'unit_cost'              => $line->getUnitCost(),
                'condition'              => $line->getCondition(),
                'notes'                  => $line->getNotes(),
                'metadata'               => $line->getMetadata()->toArray(),
                'status'                 => $line->getStatus(),
                'putaway_location_id'    => $line->getPutawayLocationId(),
            ];

            if ($line->getId()) {
                $savedModel = $this->update($line->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof GoodsReceiptLineModel) {
            throw new \RuntimeException('Failed to save GoodsReceiptLine.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findById(int $id): ?GoodsReceiptLine
    {
        $model = $this->findModel($id);

        return $model ? $this->mapModelToDomainEntity($model) : null;
    }

    public function findByGoodsReceipt(int $goodsReceiptId): Collection
    {
        return $this->model
            ->where('goods_receipt_id', $goodsReceiptId)
            ->orderBy('line_number')
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

    private function mapModelToDomainEntity(GoodsReceiptLineModel $model): GoodsReceiptLine
    {
        return new GoodsReceiptLine(
            tenantId:            $model->tenant_id,
            goodsReceiptId:      $model->goods_receipt_id,
            lineNumber:          $model->line_number,
            productId:           $model->product_id,
            quantityReceived:    (float) $model->quantity_received,
            purchaseOrderLineId: $model->purchase_order_line_id,
            variationId:         $model->variation_id,
            batchId:             $model->batch_id,
            serialNumber:        $model->serial_number,
            uomId:               $model->uom_id,
            quantityExpected:    (float) $model->quantity_expected,
            quantityAccepted:    (float) $model->quantity_accepted,
            quantityRejected:    (float) $model->quantity_rejected,
            unitCost:            (float) $model->unit_cost,
            condition:           $model->condition,
            notes:               $model->notes,
            metadata:            isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            status:              $model->status,
            putawayLocationId:   $model->putaway_location_id,
            id:                  $model->id,
            createdAt:           $model->created_at,
            updatedAt:           $model->updated_at,
        );
    }
}
