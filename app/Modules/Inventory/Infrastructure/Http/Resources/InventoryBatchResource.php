<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventoryBatchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                => $this->getId(),
            'tenant_id'         => $this->getTenantId(),
            'product_id'        => $this->getProductId(),
            'variation_id'      => $this->getVariationId(),
            'batch_number'      => $this->getBatchNumber(),
            'lot_number'        => $this->getLotNumber(),
            'manufacture_date'  => $this->getManufactureDate()?->format('Y-m-d'),
            'expiry_date'       => $this->getExpiryDate()?->format('Y-m-d'),
            'best_before_date'  => $this->getBestBeforeDate()?->format('Y-m-d'),
            'supplier_id'       => $this->getSupplierId(),
            'supplier_batch_ref'=> $this->getSupplierBatchRef(),
            'initial_qty'       => $this->getInitialQty(),
            'remaining_qty'     => $this->getRemainingQty(),
            'unit_cost'         => $this->getUnitCost(),
            'currency'          => $this->getCurrency(),
            'status'            => $this->getStatus(),
            'notes'             => $this->getNotes(),
            'metadata'          => $this->getMetadata()->toArray(),
            'created_at'        => $this->getCreatedAt()->format('c'),
            'updated_at'        => $this->getUpdatedAt()->format('c'),
        ];
    }
}
