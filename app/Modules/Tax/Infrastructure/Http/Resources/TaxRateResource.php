<?php

declare(strict_types=1);

namespace Modules\Tax\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'tax_group_id' => $this->getTaxGroupId(),
            'name' => $this->getName(),
            'rate' => $this->getRate(),
            'type' => $this->getType(),
            'account_id' => $this->getAccountId(),
            'is_compound' => $this->isCompound(),
            'is_active' => $this->isActive(),
            'valid_from' => $this->getValidFrom()?->format('Y-m-d'),
            'valid_to' => $this->getValidTo()?->format('Y-m-d'),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
