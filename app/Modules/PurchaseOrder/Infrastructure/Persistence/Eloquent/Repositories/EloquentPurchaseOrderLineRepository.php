<?php

declare(strict_types=1);

namespace Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\PurchaseOrder\Domain\Entities\PurchaseOrderLine;
use Modules\PurchaseOrder\Domain\RepositoryInterfaces\PurchaseOrderLineRepositoryInterface;
use Modules\PurchaseOrder\Infrastructure\Persistence\Eloquent\Models\PurchaseOrderLineModel;

class EloquentPurchaseOrderLineRepository extends EloquentRepository implements PurchaseOrderLineRepositoryInterface
{
    public function __construct(PurchaseOrderLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (PurchaseOrderLineModel $m): PurchaseOrderLine => $this->mapModelToDomainEntity($m));
    }

    public function save(PurchaseOrderLine $line): PurchaseOrderLine
    {
        $savedModel = null;

        DB::transaction(function () use ($line, &$savedModel) {
            $data = [
                'tenant_id'         => $line->getTenantId(),
                'purchase_order_id' => $line->getPurchaseOrderId(),
                'line_number'       => $line->getLineNumber(),
                'product_id'        => $line->getProductId(),
                'variation_id'      => $line->getVariationId(),
                'description'       => $line->getDescription(),
                'uom_id'            => $line->getUomId(),
                'quantity_ordered'  => $line->getQuantityOrdered(),
                'quantity_received' => $line->getQuantityReceived(),
                'unit_price'        => $line->getUnitPrice(),
                'discount_percent'  => $line->getDiscountPercent(),
                'tax_percent'       => $line->getTaxPercent(),
                'line_total'        => $line->getLineTotal(),
                'expected_date'     => $line->getExpectedDate(),
                'notes'             => $line->getNotes(),
                'metadata'          => $line->getMetadata(),
                'status'            => $line->getStatus(),
            ];

            if ($line->getId()) {
                $savedModel = $this->update($line->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof PurchaseOrderLineModel) {
            throw new \RuntimeException('Failed to save PurchaseOrderLine.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findByOrder(int $purchaseOrderId): Collection
    {
        return $this->model
            ->where('purchase_order_id', $purchaseOrderId)
            ->orderBy('line_number')
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(PurchaseOrderLineModel $model): PurchaseOrderLine
    {
        return new PurchaseOrderLine(
            tenantId:         $model->tenant_id,
            purchaseOrderId:  $model->purchase_order_id,
            lineNumber:       (int) $model->line_number,
            productId:        $model->product_id,
            quantityOrdered:  (float) $model->quantity_ordered,
            unitPrice:        (float) $model->unit_price,
            variationId:      $model->variation_id,
            description:      $model->description,
            uomId:            $model->uom_id,
            quantityReceived: (float) $model->quantity_received,
            discountPercent:  (float) $model->discount_percent,
            taxPercent:       (float) $model->tax_percent,
            lineTotal:        (float) $model->line_total,
            expectedDate:     $model->expected_date,
            notes:            $model->notes,
            metadata:         isset($model->metadata) ? (array) $model->metadata : null,
            status:           $model->status,
            id:               $model->id,
            createdAt:        $model->created_at,
            updatedAt:        $model->updated_at,
        );
    }
}
