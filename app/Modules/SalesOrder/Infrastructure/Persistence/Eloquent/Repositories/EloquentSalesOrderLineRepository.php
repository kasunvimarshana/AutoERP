<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Domain\ValueObjects\Metadata;
use Modules\Core\Infrastructure\Persistence\Repositories\EloquentRepository;
use Modules\SalesOrder\Domain\Entities\SalesOrderLine;
use Modules\SalesOrder\Domain\RepositoryInterfaces\SalesOrderLineRepositoryInterface;
use Modules\SalesOrder\Infrastructure\Persistence\Eloquent\Models\SalesOrderLineModel;

class EloquentSalesOrderLineRepository extends EloquentRepository implements SalesOrderLineRepositoryInterface
{
    public function __construct(SalesOrderLineModel $model)
    {
        parent::__construct($model);
        $this->setDomainEntityMapper(fn (SalesOrderLineModel $m): SalesOrderLine => $this->mapModelToDomainEntity($m));
    }

    public function save(SalesOrderLine $line): SalesOrderLine
    {
        $savedModel = null;

        DB::transaction(function () use ($line, &$savedModel) {
            $data = [
                'tenant_id'             => $line->getTenantId(),
                'sales_order_id'        => $line->getSalesOrderId(),
                'product_id'            => $line->getProductId(),
                'product_variant_id'    => $line->getProductVariantId(),
                'description'           => $line->getDescription(),
                'quantity'              => $line->getQuantity(),
                'unit_price'            => $line->getUnitPrice(),
                'tax_rate'              => $line->getTaxRate(),
                'discount_amount'       => $line->getDiscountAmount(),
                'total_amount'          => $line->getTotalAmount(),
                'unit_of_measure'       => $line->getUnitOfMeasure(),
                'status'                => $line->getStatus(),
                'warehouse_location_id' => $line->getWarehouseLocationId(),
                'batch_number'          => $line->getBatchNumber(),
                'serial_number'         => $line->getSerialNumber(),
                'notes'                 => $line->getNotes(),
                'metadata'              => $line->getMetadata()->toArray(),
            ];

            if ($line->getId()) {
                $savedModel = $this->update($line->getId(), $data);
            } else {
                $savedModel = $this->model->create($data);
            }
        });

        if (! $savedModel instanceof SalesOrderLineModel) {
            throw new \RuntimeException('Failed to save SalesOrderLine.');
        }

        return $this->mapModelToDomainEntity($savedModel);
    }

    public function findBySalesOrder(int $salesOrderId): Collection
    {
        return $this->model
            ->where('sales_order_id', $salesOrderId)
            ->get()
            ->map(fn ($m) => $this->mapModelToDomainEntity($m));
    }

    private function mapModelToDomainEntity(SalesOrderLineModel $model): SalesOrderLine
    {
        return new SalesOrderLine(
            tenantId:            $model->tenant_id,
            salesOrderId:        $model->sales_order_id,
            productId:           $model->product_id,
            quantity:            (float) $model->quantity,
            unitPrice:           (float) $model->unit_price,
            productVariantId:    $model->product_variant_id,
            description:         $model->description,
            taxRate:             (float) $model->tax_rate,
            discountAmount:      (float) $model->discount_amount,
            totalAmount:         (float) $model->total_amount,
            unitOfMeasure:       $model->unit_of_measure,
            status:              $model->status,
            warehouseLocationId: $model->warehouse_location_id,
            batchNumber:         $model->batch_number,
            serialNumber:        $model->serial_number,
            notes:               $model->notes,
            metadata:            isset($model->metadata) ? new Metadata((array) $model->metadata) : null,
            id:                  $model->id,
            createdAt:           $model->created_at,
            updatedAt:           $model->updated_at,
        );
    }
}
