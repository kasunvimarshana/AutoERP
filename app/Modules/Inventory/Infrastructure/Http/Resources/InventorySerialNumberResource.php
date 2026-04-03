<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventorySerialNumberResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->getId(),
            'tenant_id'      => $this->getTenantId(),
            'product_id'     => $this->getProductId(),
            'variation_id'   => $this->getVariationId(),
            'batch_id'       => $this->getBatchId(),
            'serial_number'  => $this->getSerialNumber(),
            'location_id'    => $this->getLocationId(),
            'status'         => $this->getStatus(),
            'purchase_price' => $this->getPurchasePrice(),
            'currency'       => $this->getCurrency(),
            'purchased_at'   => $this->getPurchasedAt()?->format('c'),
            'sold_at'        => $this->getSoldAt()?->format('c'),
            'returned_at'    => $this->getReturnedAt()?->format('c'),
            'notes'          => $this->getNotes(),
            'metadata'       => $this->getMetadata()->toArray(),
            'created_at'     => $this->getCreatedAt()->format('c'),
            'updated_at'     => $this->getUpdatedAt()->format('c'),
        ];
    }
}
