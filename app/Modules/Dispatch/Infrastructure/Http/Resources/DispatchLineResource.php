<?php

declare(strict_types=1);

namespace Modules\Dispatch\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DispatchLineResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                   => $this->getId(),
            'tenant_id'            => $this->getTenantId(),
            'dispatch_id'          => $this->getDispatchId(),
            'sales_order_line_id'  => $this->getSalesOrderLineId(),
            'product_id'           => $this->getProductId(),
            'product_variant_id'   => $this->getProductVariantId(),
            'description'          => $this->getDescription(),
            'quantity'             => $this->getQuantity(),
            'unit_of_measure'      => $this->getUnitOfMeasure(),
            'warehouse_location_id'=> $this->getWarehouseLocationId(),
            'batch_number'         => $this->getBatchNumber(),
            'serial_number'        => $this->getSerialNumber(),
            'status'               => $this->getStatus(),
            'weight'               => $this->getWeight(),
            'notes'                => $this->getNotes(),
            'metadata'             => $this->getMetadata()->toArray(),
            'created_at'           => $this->getCreatedAt()->format('c'),
            'updated_at'           => $this->getUpdatedAt()->format('c'),
        ];
    }
}
