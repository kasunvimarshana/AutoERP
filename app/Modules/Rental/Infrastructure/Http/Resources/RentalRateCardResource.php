<?php

declare(strict_types=1);

namespace Modules\Rental\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RentalRateCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'org_unit_id' => $this->getOrgUnitId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'billing_uom' => $this->getBillingUom(),
            'rate' => $this->getRate(),
            'asset_id' => $this->getAssetId(),
            'product_id' => $this->getProductId(),
            'customer_id' => $this->getCustomerId(),
            'deposit_percentage' => $this->getDepositPercentage(),
            'priority' => $this->getPriority(),
            'valid_from' => $this->getValidFrom()?->format('c'),
            'valid_to' => $this->getValidTo()?->format('c'),
            'status' => $this->getStatus(),
            'notes' => $this->getNotes(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
