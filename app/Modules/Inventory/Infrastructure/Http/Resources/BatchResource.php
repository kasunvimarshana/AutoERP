<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Inventory\Domain\Entities\Batch;

class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Batch $batch */
        $batch = $this->resource;

        return [
            'id'              => $batch->getId(),
            'tenant_id'       => $batch->getTenantId(),
            'product_id'      => $batch->getProductId(),
            'variant_id'      => $batch->getVariantId(),
            'batch_number'    => $batch->getBatchNumber(),
            'lot_number'      => $batch->getLotNumber(),
            'manufacture_date' => $batch->getManufactureDate(),
            'expiry_date'     => $batch->getExpiryDate(),
            'received_date'   => $batch->getReceivedDate(),
            'supplier_id'     => $batch->getSupplierId(),
            'status'          => $batch->getStatus(),
            'notes'           => $batch->getNotes(),
            'metadata'        => $batch->getMetadata(),
            'sales_price'     => $batch->getSalesPrice(),
        ];
    }
}
