<?php

declare(strict_types=1);

namespace Modules\SalesOrder\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->getId(),
            'tenant_id'             => $this->getTenantId(),
            'sales_order_id'        => $this->getSalesOrderId(),
            'product_id'            => $this->getProductId(),
            'product_variant_id'    => $this->getProductVariantId(),
            'description'           => $this->getDescription(),
            'quantity'              => $this->getQuantity(),
            'unit_price'            => $this->getUnitPrice(),
            'tax_rate'              => $this->getTaxRate(),
            'discount_amount'       => $this->getDiscountAmount(),
            'total_amount'          => $this->getTotalAmount(),
            'unit_of_measure'       => $this->getUnitOfMeasure(),
            'status'                => $this->getStatus(),
            'warehouse_location_id' => $this->getWarehouseLocationId(),
            'batch_number'          => $this->getBatchNumber(),
            'serial_number'         => $this->getSerialNumber(),
            'notes'                 => $this->getNotes(),
            'metadata'              => $this->getMetadata()->toArray(),
            'created_at'            => $this->getCreatedAt()->format('c'),
            'updated_at'            => $this->getUpdatedAt()->format('c'),
        ];
    }
}
