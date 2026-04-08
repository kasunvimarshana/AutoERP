<?php

declare(strict_types=1);

namespace Modules\Pricing\Infrastructure\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->getId(),
            'tenant_id'      => $this->getTenantId(),
            'name'           => $this->getName(),
            'code'           => $this->getCode(),
            'type'           => $this->getType(),
            'pricing_method' => $this->getPricingMethod(),
            'currency_code'  => $this->getCurrencyCode(),
            'start_date'     => $this->getStartDate()?->format('Y-m-d'),
            'end_date'       => $this->getEndDate()?->format('Y-m-d'),
            'is_active'      => $this->isActive(),
            'is_valid'       => $this->isValid(),
            'is_expired'     => $this->isExpired(),
            'description'    => $this->getDescription(),
            'metadata'       => $this->getMetadata()->toArray(),
            'created_at'     => $this->getCreatedAt()->format('c'),
            'updated_at'     => $this->getUpdatedAt()->format('c'),
        ];
    }
}
