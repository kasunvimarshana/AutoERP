<?php

declare(strict_types=1);

namespace Modules\Finance\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentTermResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->getId(),
            'tenant_id' => $this->resource->getTenantId(),
            'name' => $this->resource->getName(),
            'days' => $this->resource->getDays(),
            'is_default' => $this->resource->isDefault(),
            'is_active' => $this->resource->isActive(),
            'description' => $this->resource->getDescription(),
            'discount_days' => $this->resource->getDiscountDays(),
            'discount_rate' => $this->resource->getDiscountRate(),
            'created_at' => $this->resource->getCreatedAt(),
            'updated_at' => $this->resource->getUpdatedAt(),
        ];
    }
}
