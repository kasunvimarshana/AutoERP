<?php

declare(strict_types=1);

namespace Modules\Tenant\Infrastructure\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'slug' => $this->getSlug(),
            'features' => $this->getFeatures(),
            'limits' => $this->getLimits(),
            'price' => $this->getPrice(),
            'currency_code' => $this->getCurrencyCode(),
            'billing_interval' => $this->getBillingInterval(),
            'is_active' => $this->isActive(),
            'created_at' => $this->getCreatedAt()?->format('c'),
            'updated_at' => $this->getUpdatedAt()?->format('c'),
        ];
    }
}
