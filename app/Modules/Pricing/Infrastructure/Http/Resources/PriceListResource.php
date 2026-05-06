<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'tenant_id' => $this->getTenantId(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'currency_id' => $this->getCurrencyId(),
            'is_default' => $this->isDefault(),
            'valid_from' => $this->getValidFrom()?->format('Y-m-d'),
            'valid_to' => $this->getValidTo()?->format('Y-m-d'),
            'is_active' => $this->isActive(),
            'row_version' => $this->getRowVersion(),
            'created_at' => $this->getCreatedAt()->format('c'),
            'updated_at' => $this->getUpdatedAt()->format('c'),
        ];
    }
}
